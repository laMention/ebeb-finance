<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaiementEntrantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->id,
            'montant_brut'       => (float) $this->montant_brut,
            'statut'             => $this->statut,
            'reference_externe'  => $this->reference_externe,
            'operateur_source'   => $this->operateur_source,
            'qr_code_ref'        => $this->qr_code_ref,
            'description'        => $this->description,
            'date_paiement'      => $this->created_at?->format('Y-m-d H:i:s'),
            'compte_mobile_money'=> $this->whenLoaded('compte_mobile_money', fn() => [
                'uuid'         => $this->compte_mobile_money->id,
                'operateur'    => $this->compte_mobile_money->operateur,
                'numero_compte'=> $this->compte_mobile_money->numero_compte,
            ]),
            'operation'          => $this->whenLoaded('operation', fn() =>
                new OperationResource($this->operation->loadMissing('sous_operations.type_cotisation', 'sous_operations.objectif_epargne'))
            ),
        ];
    }
}
