<?php

namespace App\Services;

use App\Models\TypeCotisation;

class TypeCotisationService
{
    /**
     * Lister tous les types de cotisation avec filtres optionnels.
     */
    public function listerTypesCotisations(array $filtres = []): array
    {
        try {
            $query = TypeCotisation::query();

            if (isset($filtres['categorie'])) {
                $query->where('categorie', mettre_en_majuscule($filtres['categorie']));
            }

            if (isset($filtres['est_actif'])) {
                $query->where('est_actif', (bool) $filtres['est_actif']);
            }

            if (isset($filtres['est_obligatoire'])) {
                $query->where('est_obligatoire', (bool) $filtres['est_obligatoire']);
            }

            $types = $query->orderBy('libelle')->get();

            return [
                'success' => true,
                'message' => 'Types de cotisation récupérés avec succès',
                'data'    => $types,
                'total'   => $types->count(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors du listage des types de cotisation', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Récupérer un type de cotisation par son ID.
     */
    public function obtenirTypeCotisation(string $id): array
    {
        try {
            $type = TypeCotisation::find($id);

            if (!$type) {
                return [
                    'success' => false,
                    'message' => 'Type de cotisation non trouvé',
                    'data'    => null,
                ];
            }

            return [
                'success' => true,
                'message' => 'Type de cotisation récupéré avec succès',
                'data'    => $type,
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du type de cotisation', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Créer un nouveau type de cotisation.
     */
    public function creerTypeCotisation(array $data): array
    {
        try {
            $code = mettre_en_majuscule($data['code']);

            if (TypeCotisation::where('code', $code)->exists()) {
                return [
                    'success' => false,
                    'message' => "Un type de cotisation avec le code '$code' existe déjà",
                ];
            }

            $type = TypeCotisation::create([
                'libelle'        => mettre_en_majuscule($data['libelle']),
                'code'           => $code,
                'categorie'      => mettre_en_majuscule($data['categorie']),
                'est_obligatoire'=> $data['est_obligatoire'] ?? false,
                'est_actif'      => $data['est_actif'] ?? true,
                'description'    => $data['description'] ?? null,
            ]);

            \Log::info('Type de cotisation créé', ['id' => $type->id, 'code' => $type->code]);

            return [
                'success' => true,
                'message' => 'Type de cotisation créé avec succès',
                'data'    => $type,
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du type de cotisation', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Modifier un type de cotisation existant.
     */
    public function modifierTypeCotisation(TypeCotisation $type, array $data): array
    {
        try {
            $champsAMettreAJour = [];

            if (array_key_exists('libelle', $data)) {
                $champsAMettreAJour['libelle'] = mettre_en_majuscule($data['libelle']);
            }

            if (array_key_exists('code', $data)) {
                $nouveauCode = mettre_en_majuscule($data['code']);
                $codeExistant = TypeCotisation::where('code', $nouveauCode)
                    ->where('id', '!=', $type->id)
                    ->exists();

                if ($codeExistant) {
                    return [
                        'success' => false,
                        'message' => "Le code '$nouveauCode' est déjà utilisé par un autre type de cotisation",
                    ];
                }

                $champsAMettreAJour['code'] = $nouveauCode;
            }

            if (array_key_exists('categorie', $data)) {
                $champsAMettreAJour['categorie'] = mettre_en_majuscule($data['categorie']);
            }

            if (array_key_exists('est_obligatoire', $data)) {
                $champsAMettreAJour['est_obligatoire'] = (bool) $data['est_obligatoire'];
            }

            if (array_key_exists('est_actif', $data)) {
                $champsAMettreAJour['est_actif'] = (bool) $data['est_actif'];
            }

            if (array_key_exists('description', $data)) {
                $champsAMettreAJour['description'] = $data['description'];
            }

            if (empty($champsAMettreAJour)) {
                return [
                    'success' => false,
                    'message' => 'Aucune donnée valide à mettre à jour',
                ];
            }

            $type->update($champsAMettreAJour);

            \Log::info('Type de cotisation modifié', ['id' => $type->id]);

            return [
                'success' => true,
                'message' => 'Type de cotisation modifié avec succès',
                'data'    => $type->fresh(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la modification du type de cotisation', [
                'id'    => $type->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la modification: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Supprimer (soft delete) un type de cotisation.
     * Bloque la suppression si des cotisations ou opérations y sont rattachées.
     */
    public function supprimerTypeCotisation(TypeCotisation $type): array
    {
        try {
            if ($type->cotisations()->exists() || $type->operations()->exists()) {
                return [
                    'success' => false,
                    'message' => 'Impossible de supprimer ce type de cotisation car il est utilisé dans des cotisations ou opérations existantes',
                ];
            }

            $type->delete();

            \Log::info('Type de cotisation supprimé', ['id' => $type->id, 'code' => $type->code]);

            return [
                'success' => true,
                'message' => 'Type de cotisation supprimé avec succès',
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du type de cotisation', [
                'id'    => $type->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Activer ou désactiver un type de cotisation.
     */
    public function basculerStatut(TypeCotisation $type): array
    {
        try {
            $nouvelEtat = !$type->est_actif;
            $type->update(['est_actif' => $nouvelEtat]);

            $statut = $nouvelEtat ? 'activé' : 'désactivé';

            \Log::info("Type de cotisation $statut", ['id' => $type->id, 'est_actif' => $nouvelEtat]);

            return [
                'success'   => true,
                'message'   => "Type de cotisation $statut avec succès",
                'est_actif' => $nouvelEtat,
                'data'      => $type->fresh(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors du changement de statut du type de cotisation', [
                'id'    => $type->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }
}
