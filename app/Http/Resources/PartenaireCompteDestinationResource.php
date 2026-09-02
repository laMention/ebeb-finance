<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartenaireCompteDestinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->id,
            'libelle'          => $this->libelle,
            'type_compte'      => $this->type_compte,
            'numero_compte'    => $this->numero_compte,
            'banque_operateur' => $this->banque_operateur,
            'est_actif'        => $this->est_actif,
            'created_at'       => $this->created_at?->format('Y-m-d'),
        ];
    }
}
