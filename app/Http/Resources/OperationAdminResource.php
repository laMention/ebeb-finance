<?php

namespace App\Http\Resources;

use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sens = in_array($this->type_operation, Operation::TYPES_CREDIT) ? 'CREDIT' : 'DEBIT';

        return [
            'uuid'           => $this->id,
            'reference'      => $this->reference,
            'type_operation' => $this->type_operation,
            'libelle'        => $this->libelle ?? $this->libelleParDefaut($this->type_operation),
            'montant'        => (float) $this->montant,
            'sens'           => $sens,
            'statut'         => $this->statut,
            'date_operation' => $this->date_operation?->format('Y-m-d H:i:s'),
            'description'    => $this->description,

            // Relations chargées dans les deux vues (liste et détail)
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'uuid'      => $this->user->id,
                'nom'       => $this->user->nom,
                'prenom'    => $this->user->prenom,
                'telephone' => $this->user->telephone,
                'reference' => $this->user->reference,
                'email'     => $this->user->email,
            ] : null),

            'operateur' => $this->whenLoaded('paiement_entrant', fn () =>
                $this->paiement_entrant?->operateur_source
            ),

            'paiement_entrant' => $this->whenLoaded('paiement_entrant', fn () => $this->paiement_entrant ? [
                'uuid'              => $this->paiement_entrant->id,
                'operateur_source'  => $this->paiement_entrant->operateur_source,
                'reference_externe' => $this->paiement_entrant->reference_externe,
                'montant_brut'      => (float) $this->paiement_entrant->montant_brut,
                'statut'            => $this->paiement_entrant->statut,
                'description'       => $this->paiement_entrant->description,
            ] : null),

            'type_cotisation' => $this->whenLoaded('type_cotisation', fn () => $this->type_cotisation ? [
                'uuid'    => $this->type_cotisation->id,
                'libelle' => $this->type_cotisation->libelle,
                'code'    => $this->type_cotisation->code,
            ] : null),

            'objectif_epargne' => $this->whenLoaded('objectif_epargne', fn () => $this->objectif_epargne ? [
                'uuid'    => $this->objectif_epargne->id,
                'libelle' => $this->objectif_epargne->libelle,
            ] : null),

            // Relations chargées uniquement en vue détail (obtenirOperation)
            'sous_operations' => $this->whenLoaded('sous_operations', fn () =>
                static::collection($this->sous_operations)
            ),

            'operation_parent' => $this->whenLoaded('operation_parent', fn () => $this->operation_parent ? [
                'uuid'           => $this->operation_parent->id,
                'reference'      => $this->operation_parent->reference,
                'type_operation' => $this->operation_parent->type_operation,
                'montant'        => (float) $this->operation_parent->montant,
            ] : null),
        ];
    }

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
