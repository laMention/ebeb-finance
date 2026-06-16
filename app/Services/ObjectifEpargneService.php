<?php

namespace App\Services;

use App\Models\ObjectifEpargne;
use App\Models\User;

class ObjectifEpargneService
{
    public function obtenirObjectifActif(User $user): ?ObjectifEpargne
    {
        return ObjectifEpargne::where('user_id', $user->id)
            ->where('est_actif', true)
            ->first();
    }

    public function creerObjectif(User $user, array $data): array
    {
        if ($this->obtenirObjectifActif($user)) {
            return [
                'success' => false,
                'message' => "Vous avez déjà un objectif d'épargne actif. Modifiez-le ou supprimez-le avant d'en créer un nouveau.",
            ];
        }

        $validation = $this->validerModePrelevement($data);
        if (!$validation['success']) {
            return $validation;
        }

        $objectif = ObjectifEpargne::create([
            'user_id'         => $user->id,
            'libelle'         => $data['libelle'],
            'montant_cible'   => $data['montant_cible'],
            'montant_epargne' => $data['montant_epargne'] ?? 0,
            'date_limite'     => $data['date_limite'] ?? null,
            'type_calcul'     => $data['type_calcul'] ?? null,
            'valeur'          => $data['valeur'] ?? null,
            // 'est_actif'       => (bool) $data['est_actif'] ?? true,
            'est_actif'       => true,
        ]);

        return [
            'success' => true,
            'objectif' => $objectif,
        ];
    }

    public function mettreAjourObjectif(ObjectifEpargne $objectif, array $data): array
    {
        $validation = $this->validerModePrelevement(array_merge(
            ['type_calcul' => $objectif->type_calcul, 'valeur' => $objectif->valeur],
            $data
        ));

        if (!$validation['success']) {
            return $validation;
        }

        $objectif->update($data);

        return [
            'success'  => true,
            'objectif' => $objectif->fresh(),
        ];
    }

    public function supprimerObjectif(ObjectifEpargne $objectif): array
    {
        $objectif->delete();

        return [
            'success' => true,
            'message' => "Objectif d'épargne supprimé. L'historique des opérations est conservé.",
        ];
    }

    private function validerModePrelevement(array $data): array
    {
        $typeCalcul = $data['type_calcul'] ?? null;
        $valeur     = isset($data['valeur']) ? (float) $data['valeur'] : null;

        if ($typeCalcul !== null && $valeur === null) {
            return ['success' => false, 'message' => "Veuillez renseigner la valeur du prélèvement automatique."];
        }

        if ($valeur !== null && $typeCalcul === null) {
            return ['success' => false, 'message' => "Veuillez sélectionner un mode de prélèvement (FIXE ou POURCENTAGE)."];
        }

        if ($typeCalcul === 'POURCENTAGE' && ($valeur < 1 || $valeur > 100)) {
            return ['success' => false, 'message' => "Le pourcentage doit être compris entre 1 et 100."];
        }

        if ($typeCalcul === 'FIXE' && $valeur <= 0) {
            return ['success' => false, 'message' => "Le montant fixe doit être strictement positif."];
        }

        return ['success' => true];
    }
}
