<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObjectifEpargneAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $montantEpargne = (float) $this->montant_epargne;
        $montantCible   = (float) $this->montant_cible;
        $progression    = $montantCible > 0 ? round(($montantEpargne / $montantCible) * 100, 1) : 0;
        $montantRestant = max(0, $montantCible - $montantEpargne);
        $surplus        = max(0, $montantEpargne - $montantCible);

        if ($montantCible > 0 && $montantEpargne > $montantCible) {
            $statut = 'OBJECTIF_DEPASSE';
        } elseif ($montantCible > 0 && $montantEpargne >= $montantCible) {
            $statut = 'OBJECTIF_ATTEINT';
        } elseif ($this->date_limite !== null && $this->date_limite->toDateString() < now()->toDateString()) {
            $statut = 'EN_RETARD';
        } else {
            $statut = 'EN_COURS';
        }

        return [
            'uuid'            => $this->id,
            'libelle'         => $this->libelle,
            'montant_cible'   => $montantCible,
            'montant_epargne' => $montantEpargne,
            'montant_restant' => $montantRestant,
            'surplus'         => $surplus,
            'progression'     => $progression,
            'statut'          => $statut,
            'est_actif'       => $this->est_actif,
            'type_calcul'     => $this->type_calcul,
            'valeur'          => $this->valeur !== null ? (float) $this->valeur : null,
            'date_limite'     => $this->date_limite?->format('Y-m-d'),
            'created_at'      => $this->created_at?->format('Y-m-d'),
            'nb_operations'   => $this->operations_count ?? 0,

            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'uuid'      => $this->user->id,
                'nom'       => $this->user->nom,
                'prenom'    => $this->user->prenom,
                'telephone' => $this->user->telephone,
                'reference' => $this->user->reference,
                'email'     => $this->user->email ?? null,
            ] : null),

            'operations' => $this->whenLoaded('operations', fn () =>
                $this->operations->map(fn ($op) => [
                    'uuid'           => $op->id,
                    'reference'      => $op->reference,
                    'type_operation' => $op->type_operation,
                    'montant'        => (float) $op->montant,
                    'statut'         => $op->statut,
                    'date_operation' => $op->date_operation?->format('Y-m-d H:i:s'),
                    'canal'          => $op->paiement_entrant?->operateur_source ?? null,
                ])
            ),
        ];
    }
}
