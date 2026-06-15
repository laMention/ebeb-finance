<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteMobileMoneyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'          => $this->id,
            'operateur'     => $this->operateur,
            'numero_compte' => $this->numero_compte,
            'est_principal' => $this->est_principal,
            'est_actif'     => $this->est_actif,
            'created_at'    => $this->created_at?->format('d/m/Y H:i'),

            'moyen_paiement' => $this->whenLoaded('moyen_paiement', fn () => [
                'uuid'      => $this->moyen_paiement->id,
                'libelle'   => $this->moyen_paiement->libelle,
                'code'      => $this->moyen_paiement->code,
                'operateur' => $this->moyen_paiement->operateur,
                'logo'      => $this->moyen_paiement->logo
                    ? storage_public_path($this->moyen_paiement->logo)
                    : null,
            ]),

            'qrcode' => $this->whenLoaded('qrcode_paiement', fn () =>
                $this->qrcode_paiement
                    ? new QrcodePaiementResource($this->qrcode_paiement)
                    : null
            ),
        ];
    }
}
