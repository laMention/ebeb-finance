<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeclarationRevenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "uuid" => $this->id,
            "user_id" => $this->user_id,
            "montant_revenu" => $this->montant_revenu,
            "montant_cotisation_regime_base" => $this->montant_cotisation_regime_base,
            "montant_cotisation_regime_complementaire" => $this->montant_cotisation_regime_complementaire,
            "montant_cotisation_mensuelle" => $this->montant_cotisation_mensuelle,
            "montant_cotisation_trimestrielle" => $this->montant_cotisation_trimestrielle, 
        ];
    }
}
