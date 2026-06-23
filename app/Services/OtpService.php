<?php

namespace App\Services;

use App\Models\SessionOtp;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Carbon\Carbon;

class OtpService
{
    private const OTP_LENGTH = 6;

    /**
     * Génère et envoie un code OTP à un utilisateur
     *
     * @param User|string $user L'utilisateur ou son télephone
     * @return array Retourne le code OTP et le message
     */
    public function generateAndSend($user): array
    {
        $user_id = $user instanceof User ? $user->id : $user;

        // $user = User::where("id", $user->id)->first();
        
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
            'contexte' => 'Verification numuméro de télephone du travailleur indépendant'
        ]);

        // Implementation de l'envoi par SMS plutard

        // Envoyer l'email avec le code OTP
        if(isset($user->email) && !empty($user->email)) {
            try {
                
                Mail::to($user->email)->send(new OtpMail($code));
                return [
                    'code_otp' => $code,
                    'success' => true,
                    'message' => 'Un code OTP a été envoyé à votre adresse e-mail.',
                    // 'status_envoi' => $statutEnvoi
                ];
            } catch (\Exception $e) {
                // Supprimer l'OTP si l'email n'a pas pu être envoyé
                SessionOtp::where('user_id', $user->id)->delete();
                \Log::error(''. $e->getMessage());
                
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi du code OTP. Veuillez réessayer.',
                ];
            }
        
        }else{
            // Envoyer une reponse impossible d'envoyer par mail
            return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi du code OTP. Veuillez réessayer. Adresse email non renseignée',
                ];
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

        // Vérifier le code
        if ($otp->code_otp !== $code) {
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

    /**
     * Obtient les informations d'un OTP (pour debug uniquement)
     *
     * @param string $telephone
     * @return array|null
     */
    public function getOtpInfo(string $telephone): ?array
    {
        $user = User::where('telephone', $telephone)->first();

        $otp = SessionOtp::where('user_id', $user->id)
            ->first();

        return $otp ? (array) $otp : null;
    }
}
