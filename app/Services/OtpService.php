<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\SessionOtp;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Carbon\Carbon;

class OtpService
{
    private const OTP_LENGTH = 6;

    public function __construct(
        private NotificationConfigService $notificationConfigService,
        private NotificationService       $notificationService,
        private NotificationLogService    $notificationLogService,
    ) {
    }

    /**
     * Génère et envoie un code OTP à un utilisateur, par tous les canaux
     * actuellement activés dans la configuration des notifications
     * (EMAIL / SMS / PUSH / IN_APP) — un seul code est généré, le même est
     * transmis sur chaque canal actif. Un canal actif mais sans destinataire
     * exploitable pour cet utilisateur (pas d'email, pas de jeton d'appareil…)
     * est simplement ignoré, sans faire échouer les autres.
     *
     * @param User|string $user L'utilisateur ou son télephone
     * @param string $contexte Motif de l'envoi (inscription, connexion,
     *   réinitialisation du code PIN…) — purement informatif, la vérification
     *   ne filtre jamais par ce champ (voir `verify()`).
     * @return array Retourne le code OTP et le message
     */
    public function generateAndSend($user, string $contexte = 'Verification numéro de télephone du travailleur indépendant'): array
    {
        $user_id = $user instanceof User ? $user->id : $user;

        // Supprimer les anciens OTP non utilisés pour cet telephone
        SessionOtp::where('user_id', $user_id)->delete();

        // Générer le code OTP — durée de validité depuis les paramètres globaux
        $dureeSecondes = (int) ParametreGlobalService::get('OTP_DUREE_SECONDES', '300');
        $code      = $this->generateCode();
        $expiresAt = Carbon::now()->addSeconds($dureeSecondes);

        // Stocker le code OTP
        SessionOtp::create([
            'user_id' => $user->id,
            'code_otp' => $code,
            'est_utilise' => false,
            'expire_at' => $expiresAt,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'tentatives' => 0,
            'contexte' => $contexte,
        ]);

        $minutes = max(1, (int) ceil($dureeSecondes / 60));
        $texte   = "Votre code de vérification E-BEB Finance est : {$code}. "
            . "Il expire dans {$minutes} minute(s). Ne le partagez avec personne.";

        $canauxTentes  = [];
        $canauxReussis = [];

        // Email — canal historique, comportement inchangé (gabarit OtpMail dédié).
        if ($this->notificationConfigService->estActif('EMAIL') && !empty($user->email)) {
            $canauxTentes[] = 'EMAIL';
            try {
                Mail::to($user->email)->send(new OtpMail($code));
                $canauxReussis[] = 'email';
                $this->journaliser('EMAIL', $user, 'ENVOYE');
            } catch (\Exception $e) {
                \Log::error('Échec envoi OTP par email : ' . $e->getMessage());
                $this->journaliser('EMAIL', $user, 'ECHEC', $e->getMessage());
            }
        }

        // SMS
        if ($this->notificationConfigService->estActif('SMS') && !empty($user->telephone)) {
            $canauxTentes[] = 'SMS';
            $resultat = $this->notificationService->envoyerSMS($user, ['message' => $texte]);
            if ($resultat['envoye']) {
                $canauxReussis[] = 'SMS';
                $this->journaliser('SMS', $user, 'ENVOYE');
            } else {
                \Log::error('Échec envoi OTP par SMS : ' . ($resultat['error'] ?? 'erreur inconnue'));
                $this->journaliser('SMS', $user, 'ECHEC', $resultat['error'] ?? null);
            }
        }

        // Push — nécessite qu'un appareil ait déjà été enregistré (impossible
        // pour le tout premier OTP d'un utilisateur qui n'a jamais ouvert
        // l'application authentifiée).
        if ($this->notificationConfigService->estActif('PUSH') && DeviceToken::where('user_id', $user->id)->exists()) {
            $canauxTentes[] = 'PUSH';
            $resultat = $this->notificationService->envoyerPush($user, [
                'titre'   => 'Code de vérification',
                'message' => $texte,
            ]);
            if ($resultat['envoye']) {
                $canauxReussis[] = 'notification push';
                $this->journaliser('PUSH', $user, 'ENVOYE');
            } else {
                \Log::error('Échec envoi OTP par push : ' . ($resultat['error'] ?? 'erreur inconnue'));
                $this->journaliser('PUSH', $user, 'ECHEC', $resultat['error'] ?? null);
            }
        }

        // In-App
        if ($this->notificationConfigService->estActif('IN_APP')) {
            $canauxTentes[] = 'IN_APP';
            try {
                Notification::create([
                    'user_id'    => $user->id,
                    'canal'      => 'IN_APP',
                    'type'       => 'OTP',
                    'titre'      => 'Code de vérification',
                    'contenu'    => ['titre' => 'Code de vérification', 'message' => $texte],
                    'est_envoye' => true,
                    'envoye_le'  => now(),
                    'est_lu'     => false,
                ]);
                $canauxReussis[] = 'notification in-app';
                $this->journaliser('IN_APP', $user, 'ENVOYE');
            } catch (\Exception $e) {
                \Log::error('Échec création notification OTP in-app : ' . $e->getMessage());
                $this->journaliser('IN_APP', $user, 'ECHEC', $e->getMessage());
            }
        }

        if (empty($canauxTentes)) {
            SessionOtp::where('user_id', $user->id)->delete();
            return [
                'success' => false,
                'message' => "Aucun canal de notification n'est activé. Contactez l'administrateur.",
            ];
        }

        if (empty($canauxReussis)) {
            SessionOtp::where('user_id', $user->id)->delete();
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du code OTP. Veuillez réessayer.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Un code OTP a été envoyé par ' . $this->listerCanaux($canauxReussis) . '.',
        ];
    }

    /** « email », « email et SMS », « email, SMS et notification push »… */
    private function listerCanaux(array $canaux): string
    {
        if (count($canaux) === 1) {
            return $canaux[0];
        }
        $dernier = array_pop($canaux);
        return implode(', ', $canaux) . ' et ' . $dernier;
    }

    /**
     * Journalise l'envoi d'un OTP dans l'historique des notifications (visible
     * depuis le panel admin) — jamais le code lui-même, qui n'a rien à faire
     * dans un journal consultable durablement.
     */
    private function journaliser(string $canal, User $user, string $statut, ?string $erreur = null): void
    {
        try {
            $this->notificationLogService->enregistrer(
                canal:            $canal,
                typeNotification: 'OTP',
                destinataire:     match ($canal) {
                    'EMAIL' => $user->email ?? 'inconnu',
                    'SMS'   => $user->telephone ?? 'inconnu',
                    default => $user->id,
                },
                statut:           $statut,
                sujet:            'Code de vérification',
                contenu:          'Code de vérification (OTP)',
                messageErreur:    $erreur,
                userId:           $user->id,
            );
        } catch (\Exception $e) {
            \Log::warning('Impossible de journaliser l\'envoi OTP', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Vérifie un code OTP
     *
     * @param string $telephone Le numéro de téléphone de l'utilisateur
     * @param string $code Le code OTP à vérifier
     * @return array Contient 'success' (bool), 'message' (string)
     */
    public function verify(string $telephone, string $code): array
    {
        $user = User::where('telephone', $telephone)->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Aucun utilisateur trouvé pour ce numéro de téléphone.',
            ];
        }

        $otp = SessionOtp::where('user_id', $user->id)->first();

        // Vérifier l'existence de l'OTP
        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Aucun code OTP trouvé pour ce telephone.',
            ];
        }

        // Vérifier l'expiration
        if (Carbon::parse($otp->expire_at)->isPast()) {
            SessionOtp::where('user_id', $user->id)->delete();
            return [
                'success' => false,
                'message' => 'Le code OTP a expiré. Veuillez en demander un nouveau.',
            ];
        }

        // Vérifier le nombre de tentatives (depuis les paramètres globaux)
        $maxTentatives = (int) ParametreGlobalService::get('OTP_TENTATIVES_MAX', '3');
        if ($otp->tentatives >= $maxTentatives) {
            SessionOtp::where('user_id', $user->id)->delete();
            return [
                'success' => false,
                'message' => 'Nombre maximum de tentatives dépassé. Veuillez demander un nouveau code.',
            ];
        }

        // Nettoyer le code entré (supprimer les espaces)
        $code = str_replace(' ', '', $code);

        // Vérifier le code (comparaison à temps constant pour éviter les attaques temporelles)
        if (!hash_equals((string) $otp->code_otp, (string) $code)) {
            // Incrémenter les tentatives
            SessionOtp::where('user_id', $user->id)
                ->increment('tentatives');

            $remainingAttempts = $maxTentatives - $otp->tentatives - 1;
            return [
                'success' => false,
                'message' => "Code OTP incorrect. Il vous reste {$remainingAttempts} tentative(s).",
            ];
        }

        // Marquer le code OTP comme vérifié
        SessionOtp::where('user_id', $user->id)
            ->update([
                'est_utilise' => true,
                'updated_at' => Carbon::now(),
            ]);

        return [
            'success' => true,
            'message' => 'Code OTP vérifié avec succès.',
        ];
    }

    /**
     * Valide et supprime un OTP après utilisation
     *
     * @param string $telephone Le téléphone de l'utilisateur
     * @return bool
     */
    public function validateAndDelete(string $telephone): bool
    {
        $user = User::where('telephone', $telephone)->first();

        if (!$user) {
            return false;
        }

        $otp = SessionOtp::where('user_id', $user->id)
            ->where('est_utilise', true)
            ->first();

        if (!$otp) {
            return false;
        }

        SessionOtp::where('user_id', $user->id)->delete();
        return true;
    }

    /**
     * Vérifie si un OTP a été validé
     *
     * @param string $telephone Le téléphone de l'utilisateur
     * @return bool
     */
    public function isVerified(string $telephone): bool
    {
        $user = User::where('telephone', $telephone)->first();
        if (!$user) {
            return false;
        }
        return SessionOtp::where('user_id', $user->id)
            ->where('est_utilise', true)
            ->exists();
    }

    /**
     * Supprime les OTP expirés
     *
     * @return int Nombre d'OTP supprimés
     */
    public function deleteExpired(): int
    {
        return SessionOtp::where('expire_at', '<', Carbon::now())
                            ->delete();
    }

    /**
     * Génère un code OTP aléatoire
     *
     * @return string
     */
    private function generateCode(): string
    {
        return str_pad(random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

}
