<?php
namespace App\Services;

use App\Models\User;
use Hash;


class UserService
{
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

    

    public function deconnexion($user)
    {
        $user->tokens()->delete();

        return [
            'success' => true,
            'message' => 'Déconnexion réussie.',            
        ];

    }
}