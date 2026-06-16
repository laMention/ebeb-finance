<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QrcodePaiementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'      => $this->id,
            'reference' => $this->reference,
            'valeur'    => $this->valeur,
            'est_actif' => $this->est_actif,
        ];
    }
}
