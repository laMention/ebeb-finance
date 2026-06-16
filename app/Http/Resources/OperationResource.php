<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sens = $this->resoudreSens($this->type_operation);

        return [
            // ── Identifiants ────────────────────────────────────────────────
            'uuid'                => $this->id,
            'reference'           => $this->reference,
            'operation_parent_id' => $this->operation_parent_id,

            // ── Type / Affichage ─────────────────────────────────────────────
            'type_operation'      => $this->type_operation,
            'libelle'             => $this->libelle ?? $this->libelleParDefaut($this->type_operation),
            'description'         => $this->description,
            'categorie_icone'     => $this->resoudreIcone($this->type_operation),

            // ── Montant ──────────────────────────────────────────────────────
            'montant'             => (float) $this->montant,
            'sens'                => $sens,    // CREDIT | DEBIT

            // ── Statut & Dates ───────────────────────────────────────────────
            'statut'              => $this->statut,
            'date_operation'      => $this->date_operation?->format('Y-m-d H:i:s'),
            'date_formatee'       => $this->date_operation?->locale('fr')->isoFormat('D MMM · HH:mm'),
            'created_at'          => $this->created_at?->format('Y-m-d H:i:s'),

            // ── Relations (chargées à la demande) ────────────────────────────
            'type_cotisation'     => $this->whenLoaded('type_cotisation', fn() => [
                'uuid'    => $this->type_cotisation->id,
                'libelle' => $this->type_cotisation->libelle,
                'code'    => $this->type_cotisation->code,
            ]),
            'objectif_epargne'    => $this->whenLoaded('objectif_epargne', fn() => [
                'uuid'    => $this->objectif_epargne->id,
                'libelle' => $this->objectif_epargne->libelle,
            ]),
            'paiement_entrant'    => $this->whenLoaded('paiement_entrant', fn() => [
                'uuid'             => $this->paiement_entrant->id,
                'operateur_source' => $this->paiement_entrant->operateur_source,
                'reference_externe'=> $this->paiement_entrant->reference_externe,
            ]),
            'sous_operations'     => $this->whenLoaded('sous_operations', fn() =>
                OperationResource::collection($this->sous_operations)
            ),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function resoudreSens(string $type): string
    {
        return in_array($type, ['PAIEMENT_CLIENT', 'REVERSEMENT', 'REVERSEMENT_ESCROW'])
            ? 'CREDIT'
            : 'DEBIT';
    }

    private function resoudreIcone(string $type): string
    {
        return match ($type) {
            'PAIEMENT_CLIENT'                          => 'paiement_entrant',
            'EPARGNE'                                  => 'epargne',
            'COTISATION_CNPS'                          => 'cotisation_cnps',
            'COTISATION_AMU'                           => 'cotisation_amu',
            'COTISATION_PERSONNALISEE'                 => 'cotisation_personnalisee',
            'ASSURANCE_PERSONNALISEE'                  => 'assurance',
            'COMMISSION_PLATEFORME', 'COMMISSION'      => 'commission',
            'VIREMENT'                                 => 'virement',
            'REVERSEMENT', 'REVERSEMENT_ESCROW'        => 'reversement',
            'REPORT_COTISATION'                        => 'report',
            'AJUSTEMENT'                               => 'ajustement',
            'ESCROW'                                   => 'escrow',
            default                                    => 'autre',
        };
    }

    private function libelleParDefaut(string $type): string
    {
        return match ($type) {
            'PAIEMENT_CLIENT'         => 'Paiement reçu',
            'EPARGNE'                 => 'Épargne automatique',
            'COTISATION_CNPS'         => 'Déduction CNPS',
            'COTISATION_AMU'          => 'Déduction AMU',
            'COTISATION_PERSONNALISEE'=> 'Cotisation personnalisée',
            'ASSURANCE_PERSONNALISEE' => 'Assurance personnalisée',
            'COMMISSION_PLATEFORME'   => 'Commission plateforme',
            'COMMISSION'              => 'Commission',
            'VIREMENT'                => 'Virement Mobile Money',
            'REVERSEMENT'             => 'Reversement',
            'REVERSEMENT_ESCROW'      => 'Libération escrow',
            'REPORT_COTISATION'       => 'Report cotisation',
            'AJUSTEMENT'              => 'Ajustement',
            'ESCROW'                  => 'Blocage escrow',
            'PRELEVEMENT_COTISATION'  => 'Prélèvement cotisation',
            'PRELEVEMENT_EPARGNE'     => 'Prélèvement épargne',
            'RETRAIT_EPARGNE'         => 'Retrait épargne',
            'RETRAIT_COTISATION'      => 'Retrait cotisation',
            default                   => 'Opération',
        };
    }
}
