<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;

class DeviceTokenService
{
    /**
     * Enregistre (ou met à jour) le jeton FCM d'un appareil pour l'utilisateur
     * connecté. Si le jeton existait déjà pour un AUTRE utilisateur (appareil
     * partagé/réinstallé sous un compte différent), il est réattribué plutôt
     * que de provoquer une erreur de contrainte unique.
     */
    public function enregistrer(User $user, string $token, string $plateforme): array
    {
        try {
            $deviceToken = DeviceToken::updateOrCreate(
                ['token' => $token],
                [
                    'user_id'                 => $user->id,
                    'plateforme'               => $plateforme,
                    'derniere_utilisation_le' => now(),
                ]
            );

            return [
                'success' => true,
                'message' => 'Jeton enregistré avec succès',
                'data'    => $deviceToken,
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'enregistrement du jeton FCM', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage(),
            ];
        }
    }

    public function supprimer(User $user, string $token): array
    {
        try {
            $supprime = DeviceToken::where('user_id', $user->id)
                ->where('token', $token)
                ->delete();

            return [
                'success' => true,
                'message' => $supprime ? 'Jeton supprimé avec succès' : 'Aucun jeton correspondant trouvé',
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du jeton FCM', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ];
        }
    }
}
