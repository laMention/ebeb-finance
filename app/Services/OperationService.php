<?php

namespace App\Services;

use App\Models\Operation;
use Illuminate\Pagination\LengthAwarePaginator;

class OperationService
{
    /**
     * Liste paginée de toutes les opérations avec filtres et recherche.
     *
     * Paramètres acceptés :
     *  - recherche      (string) : référence opération ou utilisateur (nom, prénom, téléphone, référence)
     *  - type_operation (string) : filtrer sur un seul type
     *  - statut         (string) : EN_ATTENTE | SUCCES | ECHEC
     *  - operateur      (string) : WAVE | ORANGE | MTN | MOOV  (via paiement_entrant)
     *  - date_debut     (Y-m-d)  : borne inférieure sur date_operation
     *  - date_fin       (Y-m-d)  : borne supérieure sur date_operation
     *  - per_page       (int, défaut 25, max 100)
     *  - page           (int)
     */
    public function listerOperations(array $params): LengthAwarePaginator
    {
        $query = Operation::with([
            'user:id,nom,prenom,telephone,reference,email',
            'type_cotisation:id,libelle,code',
            'objectif_epargne:id,libelle',
            'paiement_entrant:id,operateur_source,reference_externe,montant_brut,statut,description',
        ])
        ->orderByDesc('date_operation')
        ->orderByDesc('created_at');

        // Recherche : référence opération ou utilisateur
        if (!empty($params['recherche'])) {
            $r = $params['recherche'];
            $query->where(function ($q) use ($r) {
                $q->where('reference', 'like', "%{$r}%")
                  ->orWhereHas('user', fn ($u) => $u
                      ->where('nom', 'like', "%{$r}%")
                      ->orWhere('prenom', 'like', "%{$r}%")
                      ->orWhere('telephone', 'like', "%{$r}%")
                      ->orWhere('reference', 'like', "%{$r}%")
                  );
            });
        }

        if (!empty($params['type_operation'])) {
            $query->where('type_operation', $params['type_operation']);
        }

        if (!empty($params['statut'])) {
            $query->where('statut', $params['statut']);
        }

        // Opérateur : filtré via la relation paiement_entrant
        if (!empty($params['operateur'])) {
            $query->whereHas('paiement_entrant', fn ($q) =>
                $q->where('operateur_source', $params['operateur'])
            );
        }

        if (!empty($params['date_debut'])) {
            $query->whereDate('date_operation', '>=', $params['date_debut']);
        }
        if (!empty($params['date_fin'])) {
            $query->whereDate('date_operation', '<=', $params['date_fin']);
        }

        $perPage = isset($params['per_page']) ? min((int) $params['per_page'], 100) : 25;
        $page    = isset($params['page'])     ? (int) $params['page'] : 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Charge une opération avec toutes ses relations pour la vue détail.
     */
    public function obtenirOperation(Operation $operation): Operation
    {
        return $operation->load([
            'user:id,nom,prenom,telephone,reference,email',
            'type_cotisation:id,libelle,code',
            'objectif_epargne:id,libelle',
            'paiement_entrant:id,operateur_source,reference_externe,montant_brut,statut,description',
            'sous_operations.type_cotisation:id,libelle,code',
            'sous_operations.objectif_epargne:id,libelle',
            'operation_parent:id,reference,type_operation,montant',
        ]);
    }

}
