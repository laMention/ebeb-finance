<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObjectifEpargneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $progression = $this->montant_cible > 0
            ? round(($this->montant_epargne / $this->montant_cible) * 100, 2)
            : 0;

        return [
            'uuid'            => $this->id,
            'libelle'         => $this->libelle,
            'montant_cible'   => (float) $this->montant_cible,
            'montant_epargne' => (float) $this->montant_epargne,
            'date_limite'     => $this->date_limite?->format('Y-m-d'),
            'type_calcul'     => $this->type_calcul,
            'valeur'          => $this->valeur !== null ? (float) $this->valeur : null,
            'est_actif'       => $this->est_actif,
            'progression'     => $progression,
            'objectif_atteint'=> $progression >= 100,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
