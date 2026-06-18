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

    /**
     * Sérialise une opération en tableau pour la réponse API.
     * Avec $detail = true, inclut aussi les sous-opérations et l'opération parente.
     */
    public function formaterOperation(Operation $op, bool $detail = false): array
    {
        $sens = in_array($op->type_operation, Operation::TYPES_CREDIT) ? 'CREDIT' : 'DEBIT';

        $data = [
            'uuid'           => $op->id,
            'reference'      => $op->reference,
            'type_operation' => $op->type_operation,
            'libelle'        => $op->libelle ?? $this->libelleParDefaut($op->type_operation),
            'montant'        => (float) $op->montant,
            'sens'           => $sens,
            'statut'         => $op->statut,
            'date_operation' => $op->date_operation?->format('Y-m-d H:i:s'),
            'description'    => $op->description,

            'user' => $op->relationLoaded('user') && $op->user ? [
                'uuid'      => $op->user->id,
                'nom'       => $op->user->nom,
                'prenom'    => $op->user->prenom,
                'telephone' => $op->user->telephone,
                'reference' => $op->user->reference,
                'email'     => $op->user->email,
            ] : null,

            'operateur' => $op->relationLoaded('paiement_entrant') && $op->paiement_entrant
                ? $op->paiement_entrant->operateur_source
                : null,

            'paiement_entrant' => $op->relationLoaded('paiement_entrant') && $op->paiement_entrant ? [
                'uuid'               => $op->paiement_entrant->id,
                'operateur_source'   => $op->paiement_entrant->operateur_source,
                'reference_externe'  => $op->paiement_entrant->reference_externe,
                'montant_brut'       => (float) $op->paiement_entrant->montant_brut,
                'statut'             => $op->paiement_entrant->statut,
                'description'        => $op->paiement_entrant->description,
            ] : null,

            'type_cotisation' => $op->relationLoaded('type_cotisation') && $op->type_cotisation ? [
                'uuid'    => $op->type_cotisation->id,
                'libelle' => $op->type_cotisation->libelle,
                'code'    => $op->type_cotisation->code,
            ] : null,

            'objectif_epargne' => $op->relationLoaded('objectif_epargne') && $op->objectif_epargne ? [
                'uuid'    => $op->objectif_epargne->id,
                'libelle' => $op->objectif_epargne->libelle,
            ] : null,
        ];

        if ($detail) {
            $data['sous_operations'] = $op->relationLoaded('sous_operations')
                ? $op->sous_operations->map(fn ($s) => $this->formaterOperation($s))->values()->all()
                : [];

            $data['operation_parent'] = $op->relationLoaded('operation_parent') && $op->operation_parent ? [
                'uuid'           => $op->operation_parent->id,
                'reference'      => $op->operation_parent->reference,
                'type_operation' => $op->operation_parent->type_operation,
                'montant'        => (float) $op->operation_parent->montant,
            ] : null;
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function libelleParDefaut(string $type): string
    {
        return match ($type) {
            'PAIEMENT_CLIENT'          => 'Paiement reçu',
            'EPARGNE'                  => 'Épargne automatique',
            'COTISATION_CNPS'          => 'Déduction CNPS',
            'COTISATION_AMU'           => 'Déduction AMU',
            'COTISATION_PERSONNALISEE' => 'Cotisation personnalisée',
            'ASSURANCE_PERSONNALISEE'  => 'Assurance personnalisée',
            'COMMISSION_PLATEFORME'    => 'Commission plateforme',
            'COMMISSION'               => 'Commission',
            'VIREMENT'                 => 'Virement Mobile Money',
            'REVERSEMENT'              => 'Reversement',
            'REVERSEMENT_ESCROW'       => 'Libération escrow',
            'REPORT_COTISATION'        => 'Report cotisation',
            'AJUSTEMENT'               => 'Ajustement',
            'ESCROW'                   => 'Blocage escrow',
            'PRELEVEMENT_COTISATION'   => 'Prélèvement cotisation',
            'PRELEVEMENT_EPARGNE'      => 'Prélèvement épargne',
            'RETRAIT_EPARGNE'          => 'Retrait épargne',
            'RETRAIT_COTISATION'       => 'Retrait cotisation',
            default                    => 'Opération',
        };
    }
}
