<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileMoneyAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $numero = $this->numero_compte ?? '';
        $masked = strlen($numero) > 4
            ? str_repeat('•', max(0, strlen($numero) - 4)) . substr($numero, -4)
            : $numero;

        return [
            'id'              => $this->id,
            'operateur'       => $this->operateur,
            'numero_compte'   => $numero,
            'numero_masque'   => $masked,
            'est_principal'   => (bool) $this->est_principal,
            'est_actif'       => (bool) $this->est_actif,
            'statut'          => $this->statut ?? 'ACTIF',

            'user' => $this->whenLoaded('user', fn () => [
                'id'        => $this->user->id,
                'nom'       => $this->user->nom,
                'prenom'    => $this->user->prenom,
                'reference' => $this->user->reference,
                'telephone' => $this->user->telephone,
            ]),

            'moyen_paiement' => $this->whenLoaded('moyen_paiement', fn () => $this->moyen_paiement ? [
                'id'        => $this->moyen_paiement->id,
                'libelle'   => $this->moyen_paiement->libelle,
                'code'      => $this->moyen_paiement->code,
                'logo'      => $this->moyen_paiement->logo,
                'operateur' => $this->moyen_paiement->operateur,
                'statut_api' => $this->moyen_paiement->relationLoaded('configurationApiActive')
                    ? ($this->moyen_paiement->configurationApiActive ? [
                        'est_actif'    => (bool) $this->moyen_paiement->configurationApiActive->est_actif,
                        'environnement'=> $this->moyen_paiement->configurationApiActive->environnement,
                        'url_api'      => $this->moyen_paiement->configurationApiActive->url_api,
                    ] : null)
                    : null,
            ] : null),

            'qrcode' => $this->whenLoaded('qrcode_paiement', fn () => $this->qrcode_paiement ? [
                'reference' => $this->qrcode_paiement->reference,
                'valeur'    => $this->qrcode_paiement->valeur,
                'est_actif' => (bool) $this->qrcode_paiement->est_actif,
            ] : null),

            'paiements_entrants_count' => $this->paiements_entrants_count ?? 0,
            'created_at'               => $this->created_at?->toISOString(),
            'updated_at'               => $this->updated_at?->toISOString(),
        ];
    }
}
