<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigurationApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'moyen_paiement_id' => $this->moyen_paiement_id,
            'moyen_paiement'   => $this->whenLoaded('moyen_paiement', fn () => [
                'id'       => $this->moyen_paiement->id,
                'libelle'  => $this->moyen_paiement->libelle,
                'code'     => $this->moyen_paiement->code,
                'logo'     => $this->moyen_paiement->logo,
                'operateur' => $this->moyen_paiement->operateur,
                'est_actif' => $this->moyen_paiement->est_actif,
            ]),
            'url_api'          => $this->url_api,
            'url_webhook'      => $this->url_webhook,
            'identifiant_api'  => $this->identifiant_api,
            'has_cle_api'      => !empty($this->cle_api),
            'cle_api_masquee'  => !empty($this->cle_api) ? str_repeat('•', 8) : null,
            'environnement'    => $this->environnement,
            'est_actif'        => $this->est_actif,
            'notes'            => $this->notes,
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
