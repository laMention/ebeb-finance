<?php
namespace App\Services;

use App\Models\User;
use Hash;

class UserService
{
    private const CONTEXTE_OTP_REINITIALISATION_PIN = 'REINITIALISATION_PIN';

    public function __construct(private OtpService $otpService)
    {
    }

    /**
     * Récupère un utilisateur avec toutes ses données liées.
     */
    public function obtenirInfosUtilisateur(int|string $userId): User
    {
        return User::where('id', $userId)
            ->with([
                'informationProfessionnelle',
                'documentKYCs',
                'declarationRevenu',
                'compteMobileMoneys',
                'enfants',
                // Le 20 dernières cotisations et les 20 dernières opérations pour éviter de surcharger la réponse
                // 'cotisations.typeCotisation',
                'cotisations' => fn ($q) => $q->latest()->limit(20)->with('typeCotisation'),
                'escrows.operation',
                // 'operations.type_cotisation',
                'operations' => fn ($q) => $q->latest()->limit(20)->with(['type_cotisation', 'objectif_epargne']),
                'operations.objectif_epargne',
                // 'paiementsEntrants',
                'paiementsEntrants' => fn ($q) => $q->latest()->limit(20),
                "reglePrelevements"
            ])
            ->firstOrFail();
    }

    public function mettreAjourProfil(User $user, array $data): array
    {
        $user->update($data);

        return [
            'success' => true,
            'user'    => $user->fresh(),
        ];
    }

    public function mettreAjourCodePin(User $user, array $data): array
    {
        if (!Hash::check($data['ancien_code_pin'], $user->password)) {
            return [
                'success' => false,
                'message' => "L'ancien code PIN est incorrect.",
            ];
        }

        $user->update(['password' => $data['nouveau_code_pin']]);

        return ['success' => true];
    }

    /**
     * Vérifie le code PIN de l'utilisateur déjà authentifié (jeton Sanctum
     * valide), sans le modifier — déverrouillage de l'application après une
     * mise en arrière-plan, la session restant active tout du long.
     */
    public function verifierCodePin(User $user, string $codePin): bool
    {
        return Hash::check($codePin, $user->password);
    }

    /**
     * Code PIN oublié — étape 1 : envoie un code de vérification (OTP) à
     * l'utilisateur déjà authentifié, pour prouver son identité avant de le
     * laisser définir un nouveau code PIN sans connaître l'ancien.
     */
    public function demanderReinitialisationCodePin(User $user): array
    {
        return $this->otpService->generateAndSend($user, self::CONTEXTE_OTP_REINITIALISATION_PIN);
    }

    /**
     * Code PIN oublié — étape 2 : vérifie le code reçu. Ne modifie rien —
     * marque seulement l'OTP comme validé (voir `OtpService::verify()`),
     * condition requise par `reinitialiserCodePin()` juste après.
     */
    public function verifierOtpReinitialisationCodePin(User $user, string $codeOtp): array
    {
        return $this->otpService->verify($user->telephone, $codeOtp);
    }

    /**
     * Code PIN oublié — étape 3 : définit le nouveau code PIN, uniquement si
     * l'étape 2 a réussi pour cet utilisateur. Consomme l'OTP (suppression)
     * pour qu'il ne puisse pas servir à une seconde réinitialisation.
     */
    public function reinitialiserCodePin(User $user, string $nouveauCodePin): array
    {
        if (!$this->otpService->isVerified($user->telephone)) {
            return [
                'success' => false,
                'message' => "Identité non vérifiée. Veuillez d'abord valider le code de vérification reçu.",
            ];
        }

        $user->update(['password' => $nouveauCodePin]);
        $this->otpService->validateAndDelete($user->telephone);

        return ['success' => true];
    }

    public function deconnexion($user)
    {
        $user->tokens()->delete();

        return [
            'success' => true,
            'message' => 'Déconnexion réussie.',            
        ];

    }
}