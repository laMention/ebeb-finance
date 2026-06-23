<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogAuditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'utilisateur'   => $this->utilisateur,
            'action'        => $this->action,
            'module'        => $this->entite_cible,
            'entite_id'     => $this->entite_id,
            'donnees_avant' => $this->donnees_avant,
            'donnees_apres' => $this->donnees_apres,
            'ip_adresse'    => $this->ip_adresse,
            'est_archive'   => (bool) $this->deleted_at,
            'created_at'    => $this->created_at?->format('Y-m-d H:i:s'),
            'deleted_at'    => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
