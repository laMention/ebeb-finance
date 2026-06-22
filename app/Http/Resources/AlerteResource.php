<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlerteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'type_alerte'              => $this->type_alerte,
            'titre'                    => $this->titre,
            'description'              => $this->description,
            'niveau'                   => $this->niveau,
            'est_lu'                   => (bool) $this->est_lu,
            'date_lecture'             => $this->date_lecture?->format('Y-m-d H:i:s'),
            'lien_vers_action_concerne'=> $this->lien_vers_action_concerne,
            'est_archive'              => (bool) $this->deleted_at,
            'created_at'               => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
