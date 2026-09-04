<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\Administrateur;
use App\Models\AdminSessionOtp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Deuxième facteur de connexion admin (2FA) — canal EMAIL pour l'instant.
 *
 * Conçu pour rester évolutif : `AdminSessionOtp.canal` distingue déjà le
 * moyen utilisé, et `demarrer()`/`verifier()` sont les deux seuls points
 * d'entrée qu'un futur canal TOTP (Google Authenticator) devrait respecter
 * pour s'intégrer sans toucher `AdminService::connexion()` ni le contrôleur.
 *
 * Aucun jeton Sanctum n'est jamais émis par ce service avant `verifier()` —
 * un administrateur dont le mot de passe est valide mais le second facteur
 * pas encore validé n'obtient donc, à aucun moment, de moyen d'accéder au
 * panel.
 */
class AdminTwoFactorService
{
    private const OTP_LENGTH = 6;

    /**
     * Étape 1 (après mot de passe validé) : génère et envoie le code, crée
     * le « challenge » que le frontend devra présenter à `verifier()`.
     */
    public function demarrer(Administrateur $admin): array
    {
        if (empty($admin->email)) {
            return [
                'success' => false,
                'message' => "Aucune adresse email n'est renseignée sur ce compte administrateur. "
                    . 'Contactez un autre administrateur pour la configurer.',
            ];
        }

        // Un seul challenge actif à la fois par administrateur.
        AdminSessionOtp::where('administrateur_id', $admin->id)->delete();

        $dureeSecondes = (int) ParametreGlobalService::get('OTP_DUREE_SECONDES', '300');
        $code = $this->genererCode();

        $otp = AdminSessionOtp::create([
            'administrateur_id' => $admin->id,
            'code_otp'          => $code,
            'canal'             => 'EMAIL',
            'est_utilise'       => false,
            'tentatives'        => 0,
            'expire_at'         => Carbon::now()->addSeconds($dureeSecondes),
        ]);

        try {
            Mail::to($admin->email)->send(new OtpMail($code));
        } catch (\Exception $e) {
            $otp->delete();
            return [
                'success' => false,
                'message' => "Erreur lors de l'envoi du code de vérification. Veuillez réessayer.",
            ];
        }

        return [
            'success'      => true,
            'message'      => "Un code de vérification a été envoyé à {$this->masquerEmail($admin->email)}.",
            'challenge_id' => $otp->id,
        ];
    }

    /**
     * Étape 2 : vérifie le code. Ne crée le jeton Sanctum (et ne charge les
     * permissions) qu'à ce moment précis — jamais avant.
     */
    public function verifier(string $challengeId, string $code): array
    {
        $otp = AdminSessionOtp::where('id', $challengeId)->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Session de vérification introuvable ou expirée. Veuillez vous reconnecter.',
            ];
        }

        if (Carbon::parse($otp->expire_at)->isPast()) {
            $otp->delete();
            return [
                'success' => false,
                'message' => 'Le code a expiré. Veuillez vous reconnecter.',
            ];
        }

        $maxTentatives = (int) ParametreGlobalService::get('OTP_TENTATIVES_MAX', '3');
        if ($otp->tentatives >= $maxTentatives) {
            $otp->delete();
            return [
                'success' => false,
                'message' => 'Nombre maximum de tentatives dépassé. Veuillez vous reconnecter.',
            ];
        }

        if (!hash_equals((string) $otp->code_otp, str_replace(' ', '', $code))) {
            $otp->increment('tentatives');
            $restantes = $maxTentatives - $otp->tentatives;
            return [
                'success' => false,
                'message' => $restantes > 0
                    ? "Code incorrect. Il vous reste {$restantes} tentative(s)."
                    : 'Nombre maximum de tentatives dépassé. Veuillez vous reconnecter.',
            ];
        }

        $admin = Administrateur::find($otp->administrateur_id);
        if (!$admin) {
            $otp->delete();
            return ['success' => false, 'message' => 'Compte administrateur introuvable.'];
        }

        // Code correct : consommé immédiatement, il ne doit plus jamais être
        // réutilisable (double-soumission, rejeu…).
        $otp->delete();

        $token = $admin->createToken('admin-token')->plainTextToken;
        $admin->setRememberToken($token);

        $admin->load(['roles']);
        $hasAllPermissions = $admin->isSuperAdmin();
        $permissions = $hasAllPermissions
            ? []
            : $admin->getAllPermissions()->pluck('name')->values()->toArray();

        return [
            'success'             => true,
            'message'             => 'Connexion réussie.',
            'admin'               => $admin,
            'token'               => 'Bearer ' . $token,
            'permissions'         => $permissions,
            'has_all_permissions' => $hasAllPermissions,
        ];
    }

    /**
     * Renvoie un nouveau code : l'ancienne ligne est supprimée et une
     * nouvelle est créée (nouveau `challenge_id`) — la réponse contient
     * toujours le `challenge_id` à jour, c'est celui-là que le frontend doit
     * désormais utiliser pour `verifier()`.
     */
    public function renvoyer(string $challengeId): array
    {
        $otp = AdminSessionOtp::where('id', $challengeId)->first();
        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Session de vérification introuvable ou expirée. Veuillez vous reconnecter.',
            ];
        }

        $admin = Administrateur::find($otp->administrateur_id);
        if (!$admin) {
            $otp->delete();
            return ['success' => false, 'message' => 'Compte administrateur introuvable.'];
        }

        $otp->delete();

        return $this->demarrer($admin);
    }

    private function genererCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * `a***@exemple.com` — confirme le canal sans révéler l'adresse complète.
     */
    private function masquerEmail(string $email): string
    {
        [$local, $domaine] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '') {
            return $email;
        }
        $visible = mb_substr($local, 0, 1);
        return "{$visible}***@{$domaine}";
    }
}
