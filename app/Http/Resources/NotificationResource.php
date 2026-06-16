<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $contenu = is_array($this->contenu) ? $this->contenu : [];

        return [
            'uuid'       => $this->id,
            'type'       => $this->type,
            'titre'      => $this->titre ?? ($contenu['titre'] ?? null),
            'message'    => $contenu['message'] ?? null,
            'est_lu'     => (bool) $this->est_lu,
            'lu_le'      => $this->lu_le?->toISOString(),
            'canal'      => $this->canal,
            'envoye_le'  => $this->envoye_le?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
