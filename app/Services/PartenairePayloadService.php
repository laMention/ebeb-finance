<?php

namespace App\Services;

use App\Models\Cotisation;
use App\Models\Operation;

class PartenairePayloadService
{
    /**
     * Construit le payload détaillé d'un Travailleur Indépendant pour une opération reversée.
     * Retourne null si aucune cotisation correspondante n'est trouvée (ex. ASSURANCE_PERSONNALISEE
     * qui ne transite pas par la table cotisations).
     */
    public function construirePourOperation(Operation $op): ?array
    {
        $operation = $op->relationLoaded('user') ? $op : $op->load('user', 'type_cotisation');

        if (!$operation->user_id || !$operation->type_cotisation_id || !$operation->date_operation) {
            return null;
        }

        $cotisation = Cotisation::with(['user.informationProfessionnelle'])
            ->where('user_id', $operation->user_id)
            ->where('type_cotisation_id', $operation->type_cotisation_id)
            ->where('mois', $operation->date_operation->month)
            ->where('annee', $operation->date_operation->year)
            ->first();

        if (!$cotisation) {
            return null;
        }

        $user      = $cotisation->user;
        $infoPro   = $user?->informationProfessionnelle;
        $tauxProgression = $cotisation->montant_objectif > 0
            ? round(($cotisation->montant_verse / $cotisation->montant_objectif) * 100, 2)
            : 0.0;

        return [
            'informations_personnelles' => [
                'nom'       => $user?->nom,
                'prenom'    => $user?->prenom,
                'telephone' => $user?->telephone,
                'email'     => $user?->email,
                'sexe'      => $user?->sexe,
                'pays'      => $user?->pays,
                'ville'     => $user?->ville,
                'quartier'  => $user?->quartier,
                'adresse_postale' => $user?->adresse_postale,
            ],
            'informations_professionnelles' => [
                'profession'                => $user?->profession,
                'categorie_professionnelle' => $infoPro?->categorie_professionnelle,
                'metier'                    => $infoPro?->metier,
                'date_debut_activite'       => $infoPro?->date_debut_activite?->format('Y-m-d'),
            ],
            'numero_adherent'    => $cotisation->numero_adherent,
            'periode_cotisation' => sprintf('%04d-%02d', $cotisation->annee, $cotisation->mois),
            'type_cotisation'    => [
                'libelle'   => $operation->type_cotisation?->libelle,
                'code'      => $operation->type_cotisation?->code,
                'categorie' => $operation->type_cotisation?->categorie,
            ],
            'montant_reverse'     => (float) $operation->montant,
            'objectif_cotisation' => (float) $cotisation->montant_objectif,
            'montant_cumule'      => (float) $cotisation->montant_verse,
            'taux_progression'    => $tauxProgression,
            'statut_conformite'   => $this->statutConformite($cotisation->statut),
        ];
    }

    private function statutConformite(string $statutCotisation): string
    {
        return match ($statutCotisation) {
            'A_JOUR', 'OBJECTIF_ATTEINT' => 'À jour',
            'EN_COURS'                   => 'Partiellement à jour',
            'NON_A_JOUR', 'REPORT'       => 'Non à jour',
            default                      => 'Non à jour',
        };
    }
}
