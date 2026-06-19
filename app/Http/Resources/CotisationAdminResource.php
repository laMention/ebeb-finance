<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CotisationAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->id,
            'mois'             => $this->mois,
            'annee'            => $this->annee,
            'montant_verse'    => (float) $this->montant_verse,
            'montant_objectif' => (float) $this->montant_objectif,
            'montant_restant'  => (float) $this->montant_restant,
            'statut'           => $this->statut,
            'numero_adherent'  => $this->numero_adherent,
            'date_paiement'    => $this->date_paiement?->format('Y-m-d H:i:s'),
            'date_debut'       => $this->date_debut?->format('Y-m-d'),
            'date_fin'         => $this->date_fin?->format('Y-m-d'),

            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'uuid'      => $this->user->id,
                'nom'       => $this->user->nom,
                'prenom'    => $this->user->prenom,
                'telephone' => $this->user->telephone,
                'reference' => $this->user->reference,
            ] : null),

            'type_cotisation' => $this->whenLoaded('typeCotisation', fn () => $this->typeCotisation ? [
                'uuid'      => $this->typeCotisation->id,
                'libelle'   => $this->typeCotisation->libelle,
                'code'      => $this->typeCotisation->code,
                'categorie' => $this->typeCotisation->categorie,
            ] : null),
        ];
    }
}
