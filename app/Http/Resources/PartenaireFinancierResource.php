<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartenaireFinancierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                      => $this->id,
            'nom'                       => $this->nom,
            'code'                      => $this->code,
            'type'                      => $this->type,
            'est_actif'                 => $this->est_actif,
            'url_api_reversement'       => $this->url_api_reversement,
            'url_api_consultation'      => $this->url_api_consultation,
            'url_webhook'               => $this->url_webhook,
            'methode_authentification'  => $this->methode_authentification,
            // identifiants_api n'est jamais exposé — secret chiffré, write-only via l'API.
            'a_identifiants_configures' => !empty($this->identifiants_api),
            'format_echange'            => $this->format_echange,
            'comptes_destination'       => PartenaireCompteDestinationResource::collection($this->whenLoaded('comptesDestination')),
            'nb_reversements'           => $this->reversements_count ?? 0,
            'created_at'                => $this->created_at?->format('Y-m-d'),
        ];
    }
}
