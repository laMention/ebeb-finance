<?php

namespace App\Services;

use App\Models\ReglePrelevement;
use App\Models\TypeCotisation;
use App\Models\User;
use DB;

class ReglePrelevementService
{
    /**
     * Lister toutes les règles de prélèvement d'un utilisateur avec tri par priorité.
     */
    public function listerReglesUtilisateur(string $userId): array
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

            $regles = $user->reglePrelevements()
                ->with('type_cotisation')
                ->orderBy('ordre_priorite')
                ->get();

            return [
                'success' => true,
                'message' => 'Règles de prélèvement récupérées avec succès',
                'data'    => $regles,
                'total'   => $regles->count(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des règles de prélèvement', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Récupérer une règle de prélèvement spécifique.
     */
    public function obtenirRegle(string $regleId, string $userId = null): array
    {
        try {
            $query = ReglePrelevement::with('type_cotisation');

            if ($userId) {
                $query->where('user_id', $userId);
            }

            $regle = $query->find($regleId);

            if (!$regle) {
                return [
                    'success' => false,
                    'message' => 'Règle de prélèvement non trouvée',
                    'data'    => null,
                ];
            }

            return [
                'success' => true,
                'message' => 'Règle récupérée avec succès',
                'data'    => $regle,
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de la règle', [
                'regle_id' => $regleId,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Créer ou mettre à jour une règle (updateOrCreate).
     * Si une règle existe pour ce type de cotisation, elle est modifiée.
     * Sinon, une nouvelle est créée.
     * Utilisé par l'écran mobile pour mettre à jour les champs taux/montant.
     */
    public function sauvegarderRegle(string $userId, array $data): array
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur non trouvé',
                ];
            }

            $typeCotisation = TypeCotisation::find($data['type_cotisation_id']);

            if (!$typeCotisation) {
                return [
                    'success' => false,
                    'message' => 'Type de cotisation non trouvé',
                ];
            }

            if (!$typeCotisation->est_actif) {
                return [
                    'success' => false,
                    'message' => 'Ce type de cotisation est inactif',
                ];
            }

            // Valider la valeur selon le type de calcul
            $typeCalcul = mettre_en_majuscule($data['type_calcul']);
            $valeur = (float) $data['valeur'];

            $validationValeur = $this->validerValeur($typeCalcul, $valeur);

            if (!$validationValeur['valid']) {
                return [
                    'success' => false,
                    'message' => $validationValeur['message'],
                ];
            }

            // Chercher une règle existante pour ce type
            $regleExistante = ReglePrelevement::where('user_id', $userId)
                ->where('type_cotisation_id', $data['type_cotisation_id'])
                ->first();

            $isCreation = !$regleExistante;

            // Déterminer l'ordre de priorité
            $ordrePriorite = $isCreation
                ? (ReglePrelevement::where('user_id', $userId)->max('ordre_priorite') ?? 0) + 1
                : $regleExistante->ordre_priorite;

            // UpdateOrCreate
            $regle = ReglePrelevement::updateOrCreate(
                [
                    'user_id'            => $userId,
                    'type_cotisation_id' => $data['type_cotisation_id'],
                ],
                [
                    'type_calcul'    => $typeCalcul,
                    'valeur'         => $valeur,
                    'est_actif'      => $data['est_actif'] ?? true,
                    'ordre_priorite' => $ordrePriorite,
                ]
            );

            $action = $isCreation ? 'créée' : 'modifiée';

            \Log::info("Règle de prélèvement $action", [
                'regle_id'           => $regle->id,
                'user_id'            => $userId,
                'type_cotisation_id' => $data['type_cotisation_id'],
                'is_creation'        => $isCreation,
            ]);

            return [
                'success' => true,
                'message' => "Règle de prélèvement $action avec succès",
                'data'    => $regle->load('type_cotisation'),
                'created' => $isCreation,
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la sauvegarde de la règle', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Supprimer (soft delete) une règle de prélèvement.
     */
    public function supprimerRegle(ReglePrelevement $regle): array
    {
        try {
            $regleId = $regle->id;
            $regle->delete();

            \Log::info('Règle de prélèvement supprimée', ['regle_id' => $regleId]);

            return [
                'success' => true,
                'message' => 'Règle de prélèvement supprimée avec succès',
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de la règle', [
                'regle_id' => $regle->id,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Activer ou désactiver une règle de prélèvement.
     */
    public function basculerStatut(ReglePrelevement $regle): array
    {
        try {
            $nouvelEtat = !$regle->est_actif;
            $regle->update(['est_actif' => $nouvelEtat]);

            $statut = $nouvelEtat ? 'activée' : 'désactivée';

            \Log::info("Règle de prélèvement $statut", ['regle_id' => $regle->id, 'est_actif' => $nouvelEtat]);

            return [
                'success'  => true,
                'message'  => "Règle de prélèvement $statut avec succès",
                'est_actif' => $nouvelEtat,
                'data'     => $regle->fresh(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors du changement de statut', [
                'regle_id' => $regle->id,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Réorganiser les règles en changeant leur ordre de priorité.
     * @param string $userId
     * @param array $ordres Array avec structure: [['regle_id' => 'xxx', 'ordre_priorite' => 1], ...]
     */
    public function reordonnerRegles(string $userId, array $ordres): array
    {
        try {
            DB::beginTransaction();

            $user = User::find($userId);

            if (!$user) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Utilisateur non trouvé',
                ];
            }

            // Vérifier que toutes les règles appartiennent à l'utilisateur
            foreach ($ordres as $item) {
                $regle = ReglePrelevement::find($item['regle_id']);

                if (!$regle || $regle->user_id !== $userId) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => 'Une des règles n\'appartient pas à cet utilisateur',
                    ];
                }

                // Mettre à jour l'ordre de priorité
                $regle->update(['ordre_priorite' => (int) $item['ordre_priorite']]);
            }

            DB::commit();

            \Log::info('Règles de prélèvement réordonnées', [
                'user_id' => $userId,
                'count'   => count($ordres),
            ]);

            $reglesTriees = $user->reglePrelevements()
                ->with('type_cotisation')
                ->orderBy('ordre_priorite')
                ->get();

            return [
                'success' => true,
                'message' => 'Ordre de priorité mis à jour avec succès',
                'data'    => $reglesTriees,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Erreur lors de la réorganisation des règles', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la réorganisation: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Récupérer tous les types de cotisations avec les règles configurées par l'utilisateur.
     * Inclut les types de cotisations globaux (est_actif) ET les types personnalisés de l'utilisateur.
     * Utilisé par l'interface mobile pour afficher les types et pré-remplir les champs.
     */
    public function obtenirTypeCotisationsAvecRegles(string $userId): array
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

            // Récupérer les types de cotisations globaux (user_id = NULL) et actifs
            $typesGlobaux = TypeCotisation::where('est_actif', true)
                ->whereNull('user_id')
                ->orderBy('libelle')
                ->get();

            // Récupérer les types de cotisations personnalisés de l'utilisateur
            $typesPersonnalises = TypeCotisation::where('user_id', $userId)
                ->orderBy('libelle')
                ->get();

            // Fusionner les deux ensembles
            $types = $typesGlobaux->concat($typesPersonnalises);

            // Récupérer les règles de l'utilisateur indexées par type_cotisation_id
            $reglesUtilisateur = $user->reglePrelevements()
                ->get()
                ->keyBy('type_cotisation_id');

            // Fusionner les données
            $typesAvecRegles = $types->map(function ($type) use ($reglesUtilisateur) {
                $regle = $reglesUtilisateur->get($type->id);

                return [
                    'id'                  => $type->id,
                    'libelle'             => $type->libelle,
                    'code'                => $type->code,
                    'categorie'           => $type->categorie,
                    'est_obligatoire'     => $type->est_obligatoire,
                    'description'         => $type->description,
                    'est_personnalise'    => !is_null($type->user_id),
                    'regle'               => $regle ? [
                        'id'             => $regle->id,
                        'taux'           => $regle->type_calcul === 'POURCENTAGE' ? $regle->valeur : 0,
                        'montant'        => $regle->type_calcul === 'FIXE' ? $regle->valeur : 0,
                        'type_calcul'    => $regle->type_calcul,
                        'est_actif'      => $regle->est_actif,
                        'ordre_priorite' => $regle->ordre_priorite,
                    ] : null,
                ];
            });

            // Calculer la somme totale des pourcentages actifs
            $sommePourcentages = $reglesUtilisateur
                ->filter(fn($r) => $r->est_actif && $r->type_calcul === 'POURCENTAGE')
                ->sum('valeur');

            return [
                'success'               => true,
                'message'               => 'Types de cotisations récupérés avec succès',
                'data'                  => $typesAvecRegles,
                'total_pourcentages'    => $sommePourcentages,
                'total_types'           => $typesAvecRegles->count(),
                'total_configures'      => $reglesUtilisateur->count(),
                'total_personnalises'   => $typesPersonnalises->count(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des types avec règles', [
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
     * Valider et sauvegarder plusieurs règles en une seule transaction.
     * Garantit l'intégrité des données (pas de doublons, sommes cohérentes, etc.).
     *
     * @param string $userId
     * @param array $regles Format: [['type_cotisation_id' => 'xxx', 'type_calcul' => 'POURCENTAGE', 'valeur' => 12, 'est_actif' => true], ...]
     */
    public function validerEtSauvegarderRegles(string $userId, array $regles): array
    {
        try {
            DB::beginTransaction();

            $user = User::find($userId);

            if (!$user) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Utilisateur non trouvé',
                ];
            }

            $sommePourcentages = 0;
            $typesCotisationsIds = [];
            $reglesValidees = [];

            // Valider chaque règle
            foreach ($regles as $index => $regleData) {
                if (empty($regleData['type_cotisation_id'])) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => "Type de cotisation manquant à l'index $index",
                    ];
                }

                $typeId = $regleData['type_cotisation_id'];

                // Vérifier qu'on n'a pas de doublon dans la requête
                if (in_array($typeId, $typesCotisationsIds)) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => "Doublon détecté: type de cotisation '$typeId' apparaît plusieurs fois",
                    ];
                }

                $typesCotisationsIds[] = $typeId;

                // Vérifier que le type existe et est actif
                $type = TypeCotisation::find($typeId);

                if (!$type) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => "Type de cotisation '$typeId' (index $index) non trouvé",
                    ];
                }

                if (!$type->est_actif) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => "Type de cotisation '{$type->libelle}' est inactif (index $index)",
                    ];
                }

                // Valider type_calcul et valeur
                $typeCalcul = mettre_en_majuscule($regleData['type_calcul']);
                $valeur = isset($regleData['valeur']) ? (float) $regleData['valeur'] : 0;

                $validationValeur = $this->validerValeur($typeCalcul, $valeur);

                if (!$validationValeur['valid']) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => "Validation échouée pour {$type->libelle} (index $index): {$validationValeur['message']}",
                    ];
                }

                if ($typeCalcul === 'POURCENTAGE' && $regleData['est_actif'] ?? true) {
                    $sommePourcentages += $valeur;
                }

                $reglesValidees[] = [
                    'type_cotisation_id' => $typeId,
                    'type_calcul'        => $typeCalcul,
                    'valeur'             => $valeur,
                    'est_actif'          => $regleData['est_actif'] ?? true,
                ];
            }

            // Vérifier que la somme totale des pourcentages ne dépasse pas 100%
            if ($sommePourcentages > 100) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => "La somme des pourcentages actifs ($sommePourcentages %) ne peut pas dépasser 100 %",
                ];
            }

            // Supprimer les règles actuelles de l'utilisateur
            $user->reglePrelevements()->delete();

            // Créer les nouvelles règles
            $ordre = 1;

            foreach ($reglesValidees as $regleData) {
                ReglePrelevement::create([
                    'user_id'            => $userId,
                    'type_cotisation_id' => $regleData['type_cotisation_id'],
                    'type_calcul'        => $regleData['type_calcul'],
                    'valeur'             => $regleData['valeur'],
                    'est_actif'          => $regleData['est_actif'],
                    'ordre_priorite'     => $ordre++,
                ]);
            }

            DB::commit();

            \Log::info('Règles de prélèvement sauvegardées en masse', [
                'user_id'            => $userId,
                'count'              => count($reglesValidees),
                'total_pourcentages' => $sommePourcentages,
            ]);

            // Retourner les règles sauvegardées
            $reglesSauvegardees = $user->reglePrelevements()
                ->with('type_cotisation')
                ->orderBy('ordre_priorite')
                ->get();

            return [
                'success'               => true,
                'message'               => 'Règles de prélèvement sauvegardées avec succès',
                'data'                  => $reglesSauvegardees,
                'total_pourcentages'    => $sommePourcentages,
                'count'                 => count($reglesValidees),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Erreur lors de la sauvegarde des règles', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Valider une valeur selon le type de calcul.
     */
    private function validerValeur(string $typeCalcul, float $valeur): array
    {
        if ($typeCalcul === 'POURCENTAGE') {
            if ($valeur < 0 || $valeur > 100) {
                return [
                    'valid'   => false,
                    'message' => 'Pour un pourcentage, la valeur doit être entre 0 et 100',
                ];
            }
        } elseif ($typeCalcul === 'FIXE') {
            if ($valeur <= 0) {
                return [
                    'valid'   => false,
                    'message' => 'Pour un montant fixe, la valeur doit être supérieure à 0',
                ];
            }
        } else {
            return [
                'valid'   => false,
                'message' => "Type de calcul '$typeCalcul' non reconnu. Valeurs autorisées: FIXE, POURCENTAGE",
            ];
        }

        return ['valid' => true];
    }
}
