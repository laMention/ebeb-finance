<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeuilPrelevementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'seuil_pourcentage' => $this->seuil_pourcentage,
            'seuil_montant'     => $this->seuil_montant,
            'est_actif'         => $this->est_actif,
            'description'       => $this->description,
            'modifie_par'       => $this->modifie_par,
            'updated_at'        => $this->updated_at?->format('Y-m-d H:i'),
        ];
    }
}
