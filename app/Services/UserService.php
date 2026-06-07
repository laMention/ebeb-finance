<?php
namespace App\Services;

use App\Models\User;


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
                'cotisations.typeCotisation',
                'escrows.operation',         
                'operations.type_cotisation',
                'operations.objectif_epargne',
                'paiementsEntrants',
                "reglePrelevements"
            ])
            ->firstOrFail();
    }
}