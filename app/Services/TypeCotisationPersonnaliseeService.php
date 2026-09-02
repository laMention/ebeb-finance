<?php

namespace App\Services;

use App\Models\TypeCotisation;
use App\Models\User;
use Carbon\Carbon;

class TypeCotisationPersonnaliseeService
{
    /**
     * Créer un type de cotisation personnalisé pour un utilisateur.
     */
    public function creerTypePersonnalise(string $userId, array $data): array
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur non trouvé',
                ];
            }

            $code = mettre_en_majuscule($data['code']);

            // Vérifier l'unicité du code pour cet utilisateur (un utilisateur ne peut pas avoir deux types avec le même code)
            $typeExistant = TypeCotisation::where('user_id', $userId)
                ->where('code', $code)
                ->first();

            if ($typeExistant) {
                return [
                    'success' => false,
                    'message' => "Vous avez déjà un type de cotisation avec le code '$code'",
                ];
            }

            $type = TypeCotisation::create([
                'libelle'        => mettre_en_majuscule($data['libelle']),
                'code'           => $code,
                'categorie'      => mettre_en_majuscule($data['categorie'] ?? 'PERSONNALISEE'),
                'description'    => $data['description'] ?? null,
                'montant_paiement_mensuel' => $data['montant_paiement_mensuel'] ?? null,
                'default_date_entree_en_vigueur' => Carbon::now(),
                'est_obligatoire'=> false,
                'est_actif'      => true,
                'user_id'        => $userId,
            ]);

            \Log::info('Type de cotisation personnalisé créé', [
                'type_id' => $type->id,
                'user_id' => $userId,
                'code'    => $code,
                'montant_paiement_mensuel' => $data['montant_paiement_mensuel'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => 'Type de cotisation personnalisé créé avec succès',
                'data'    => $type,
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du type personnalisé', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Suggestions de types de cotisations personnalisés déjà créés par
     * d'autres utilisateurs (libellé/code/catégorie uniquement — jamais leur
     * montant ni leur description, propres à chacun). Sert à proposer une
     * saisie cohérente sans dupliquer les informations communes : choisir une
     * suggestion crée toujours une nouvelle ligne pour l'utilisateur courant,
     * sans jamais modifier le type d'origine.
     */
    public function suggestionsTypesPersonnalises(string $userId): array
    {
        try {
            $suggestions = TypeCotisation::whereNotNull('user_id')
                ->where('user_id', '!=', $userId)
                ->where('est_actif', true)
                ->select('code', 'libelle', 'categorie')
                ->get()
                ->unique('code')
                ->sortBy('libelle')
                ->values()
                ->map(fn (TypeCotisation $type) => [
                    'code'      => $type->code,
                    'libelle'   => $type->libelle,
                    'categorie' => $type->categorie,
                ]);

            return [
                'success' => true,
                'message' => 'Suggestions récupérées avec succès',
                'data'    => $suggestions,
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors du listage des suggestions de types personnalisés', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Lister les types de cotisations personnalisés de l'utilisateur.
     */
    public function listerTypesPersonnalises(string $userId): array
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur non trouvé',
                    'data'    => [],
                ];
            }

            $types = TypeCotisation::where('user_id', $userId)
                ->orderBy('libelle')
                ->get();

            return [
                'success' => true,
                'message' => 'Types personnalisés récupérés avec succès',
                'data'    => $types,
                'total'   => $types->count(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors du listage des types personnalisés', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Obtenir un type de cotisation personnalisé.
     * Vérification que c'est bien un type personnel et qu'il appartient à l'utilisateur.
     */
    public function obtenirTypePersonnalise(string $typeId, string $userId): array
    {
        try {
            $type = TypeCotisation::where('id', $typeId)
                ->where('user_id', $userId)
                ->first();

            if (!$type) {
                return [
                    'success' => false,
                    'message' => 'Type de cotisation personnalisé non trouvé',
                    'data'    => null,
                ];
            }

            return [
                'success' => true,
                'message' => 'Type récupéré avec succès',
                'data'    => $type,
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du type', [
                'type_id' => $typeId,
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Modifier un type de cotisation personnalisé.
     */
    public function modifierTypePersonnalise(TypeCotisation $type, string $userId, array $data): array
    {
        try {
            // Vérifier que le type appartient à l'utilisateur
            if ($type->user_id !== $userId) {
                return [
                    'success' => false,
                    'message' => 'Accès non autorisé',
                ];
            }

            $champsAMettreAJour = [];

            if (array_key_exists('libelle', $data)) {
                $champsAMettreAJour['libelle'] = mettre_en_majuscule($data['libelle']);
            }

            if (array_key_exists('code', $data)) {
                $nouveauCode = mettre_en_majuscule($data['code']);

                // Vérifier l'unicité du code pour cet utilisateur
                $typeExistant = TypeCotisation::where('user_id', $userId)
                    ->where('code', $nouveauCode)
                    ->where('id', '!=', $type->id)
                    ->first();

                if ($typeExistant) {
                    return [
                        'success' => false,
                        'message' => "Vous avez déjà un type de cotisation avec le code '$nouveauCode'",
                    ];
                }

                $champsAMettreAJour['code'] = $nouveauCode;
            }

            if (array_key_exists('description', $data)) {
                $champsAMettreAJour['description'] = $data['description'];
            }

            if (array_key_exists('est_actif', $data)) {
                $champsAMettreAJour['est_actif'] = (bool) $data['est_actif'];
            }

            if (array_key_exists('valeur_par_defaut', $data)) {
                $champsAMettreAJour['default_valeur'] = $data['valeur_par_defaut'];
            }

            if (array_key_exists('montant_paiement_mensuel', $data)) {
                $champsAMettreAJour['montant_paiement_mensuel'] = $data['montant_paiement_mensuel'];
            }

            if (empty($champsAMettreAJour)) {
                return [
                    'success' => false,
                    'message' => 'Aucune donnée valide à mettre à jour',
                ];
            }

            $type->update($champsAMettreAJour);

            \Log::info('Type de cotisation personnalisé modifié', ['type_id' => $type->id]);

            return [
                'success' => true,
                'message' => 'Type de cotisation modifié avec succès',
                'data'    => $type->fresh(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la modification du type', [
                'type_id' => $type->id,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la modification: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Supprimer un type de cotisation personnalisé.
     * Bloque la suppression s'il existe des règles de prélèvement associées.
     */
    public function supprimerTypePersonnalise(TypeCotisation $type, string $userId): array
    {
        try {
            // Vérifier que le type appartient à l'utilisateur
            if ($type->user_id !== $userId) {
                return [
                    'success' => false,
                    'message' => 'Accès non autorisé',
                ];
            }

            // Vérifier s'il y a des règles de prélèvement associées
            if ($type->regle_prelevements()->exists()) {
                return [
                    'success' => false,
                    'message' => 'Impossible de supprimer ce type car il est utilisé dans vos règles de prélèvement',
                ];
            }

            $typeId = $type->id;
            $type->delete();

            \Log::info('Type de cotisation personnalisé supprimé', [
                'type_id' => $typeId,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'message' => 'Type de cotisation supprimé avec succès',
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du type', [
                'type_id' => $type->id,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ];
        }
    }
}
