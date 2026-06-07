<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InformationProfessionnelleResource extends JsonResource
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
            "categorie_professionnelle" => $this->categorie_professionnelle,
            "metier" => $this->metier,
            "revenu_mensuel" => $this->revenu_mensuel,
            "date_debut_activite" => format_date_fr_lettre($this->date_debut_activite),
            "date_fin_activite" => format_date_fr_lettre($this->date_fin_activite), 
        ];
    }
}
