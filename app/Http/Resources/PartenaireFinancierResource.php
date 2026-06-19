<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartenaireFinancierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'            => $this->id,
            'nom'             => $this->nom,
            'code'            => $this->code,
            'type'            => $this->type,
            'nb_reversements' => $this->reversements_count ?? 0,
            'created_at'      => $this->created_at?->format('Y-m-d'),
        ];
    }
}
