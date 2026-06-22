<?php

namespace App\Services;

use App\Http\Resources\SeuilPrelevementResource;
use App\Models\SeuilPrelevement;

class SeuilPrelevementService
{
    /**
     * Retourne la configuration courante des seuils.
     */
    public function obtenir(): array
    {
        try {
            $seuil = SeuilPrelevement::singleton();
            return [
                'success' => true,
                'message' => 'Configuration des seuils récupérée',
                'data'    => new SeuilPrelevementResource($seuil),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Met à jour (ou crée) la configuration des seuils.
     */
    public function mettreAJour(array $data, string $adminNom): array
    {
        try {
            $seuil = SeuilPrelevement::singleton();

            $champs = ['modifie_par' => $adminNom];

            if (array_key_exists('seuil_pourcentage', $data)) {
                $champs['seuil_pourcentage'] = $data['seuil_pourcentage'] !== '' ? (float) $data['seuil_pourcentage'] : null;
            }
            if (array_key_exists('seuil_montant', $data)) {
                $champs['seuil_montant'] = $data['seuil_montant'] !== '' ? (float) $data['seuil_montant'] : null;
            }
            if (array_key_exists('est_actif', $data)) {
                $champs['est_actif'] = (bool) $data['est_actif'];
            }
            if (array_key_exists('description', $data)) {
                $champs['description'] = $data['description'] ?: null;
            }

            $seuil->update($champs);

            return [
                'success' => true,
                'message' => 'Seuils de prélèvement mis à jour avec succès',
                'data'    => new SeuilPrelevementResource($seuil->fresh()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    // -------------------------------------------------------------------------
    // Méthode statique utilisée par ReglePrelevementService
    // -------------------------------------------------------------------------

    /**
     * Vérifie que les sommes données respectent les seuils actifs.
     * Retourne ['valid' => true] ou ['valid' => false, 'message' => '...'].
     */
    public static function verifierSeuils(float $sommePourcentages, float $sommeMontants): array
    {
        $seuil = SeuilPrelevement::first();

        if (!$seuil || !$seuil->est_actif) {
            return ['valid' => true];
        }

        if ($seuil->seuil_pourcentage !== null && $sommePourcentages > $seuil->seuil_pourcentage) {
            return [
                'valid'   => false,
                'message' => sprintf(
                    'La somme de vos prélèvements en pourcentage (%.2f %%) dépasse le seuil maximum autorisé (%.2f %%).',
                    $sommePourcentages,
                    $seuil->seuil_pourcentage
                ),
            ];
        }

        if ($seuil->seuil_montant !== null && $sommeMontants > $seuil->seuil_montant) {
            return [
                'valid'   => false,
                'message' => sprintf(
                    'La somme de vos prélèvements en montant fixe (%s FCFA) dépasse le seuil maximum autorisé (%s FCFA).',
                    number_format($sommeMontants, 0, ',', ' '),
                    number_format($seuil->seuil_montant, 0, ',', ' ')
                ),
            ];
        }

        return ['valid' => true];
    }
}
