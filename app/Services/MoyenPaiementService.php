<?php

namespace App\Services;

use App\Models\MoyenPaiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MoyenPaiementService
{
    public function listerMoyensPaiement(array $filtres = []): array
    {
        try {
            $query = MoyenPaiement::query();

            if (isset($filtres['est_actif'])) {
                $query->where('est_actif', (bool) $filtres['est_actif']);
            }

            if (isset($filtres['operateur'])) {
                $query->where('operateur', 'like', '%'.mettre_en_majuscule($filtres['operateur']).'%');
            }

            $moyens = $query->orderBy('libelle')->get();

            return [
                'success' => true,
                'message' => 'Moyens de paiement récupérés avec succès',
                'data'    => $moyens,
                'total'   => $moyens->count(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors du listage des moyens de paiement', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    public function obtenirMoyenPaiement(string $id): array
    {
        try {
            $moyen = MoyenPaiement::find($id);

            if (!$moyen) {
                return [
                    'success' => false,
                    'message' => 'Moyen de paiement non trouvé',
                    'data'    => null,
                ];
            }

            return [
                'success' => true,
                'message' => 'Moyen de paiement récupéré avec succès',
                'data'    => $moyen,
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du moyen de paiement', [
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

    public function creerMoyenPaiement(array $data): array
    {
        try {
            $code = mettre_en_majuscule($data['code']);

            if (MoyenPaiement::where('code', $code)->exists()) {
                return [
                    'success' => false,
                    'message' => "Un moyen de paiement avec le code '$code' existe déjà",
                ];
            }

            DB::beginTransaction();

            $parDefaut = (bool) ($data['par_defaut'] ?? false);

            if ($parDefaut) {
                MoyenPaiement::where('par_defaut', true)->update(['par_defaut' => false]);
            }

            $moyen = MoyenPaiement::create([
                'libelle'    => mettre_en_majuscule($data['libelle']),
                'code'       => $code,
                'logo'       => $data['logo'] ?? null,
                'operateur'  => mettre_en_majuscule($data['operateur']),
                'par_defaut' => $parDefaut,
                'est_actif'  => (bool) ($data['est_actif'] ?? true),
            ]);

            DB::commit();

            \Log::info('Moyen de paiement créé', ['id' => $moyen->id, 'code' => $moyen->code]);

            return [
                'success' => true,
                'message' => 'Moyen de paiement créé avec succès',
                'data'    => $moyen,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Erreur lors de la création du moyen de paiement', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage(),
            ];
        }
    }

    public function modifierMoyenPaiement(MoyenPaiement $moyen, array $data): array
    {
        try {
            $champsAMettreAJour = [];

            if (array_key_exists('libelle', $data)) {
                $champsAMettreAJour['libelle'] = mettre_en_majuscule($data['libelle']);
            }

            if (array_key_exists('code', $data)) {
                $nouveauCode = mettre_en_majuscule($data['code']);
                $codeExistant = MoyenPaiement::where('code', $nouveauCode)
                    ->where('id', '!=', $moyen->id)
                    ->exists();

                if ($codeExistant) {
                    return [
                        'success' => false,
                        'message' => "Le code '$nouveauCode' est déjà utilisé par un autre moyen de paiement",
                    ];
                }

                $champsAMettreAJour['code'] = $nouveauCode;
            }

            if (array_key_exists('logo', $data)) {
                $champsAMettreAJour['logo'] = $data['logo'];
            }

            if (array_key_exists('operateur', $data)) {
                $champsAMettreAJour['operateur'] = mettre_en_majuscule($data['operateur']);
            }

            if (array_key_exists('est_actif', $data)) {
                $champsAMettreAJour['est_actif'] = (bool) $data['est_actif'];
            }

            if (empty($champsAMettreAJour)) {
                return [
                    'success' => false,
                    'message' => 'Aucune donnée valide à mettre à jour',
                ];
            }

            $moyen->update($champsAMettreAJour);

            \Log::info('Moyen de paiement modifié', ['id' => $moyen->id]);

            return [
                'success' => true,
                'message' => 'Moyen de paiement modifié avec succès',
                'data'    => $moyen->fresh(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la modification du moyen de paiement', [
                'id'    => $moyen->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la modification: ' . $e->getMessage(),
            ];
        }
    }

    public function supprimerMoyenPaiement(MoyenPaiement $moyen): array
    {
        try {
            if ($moyen->comptes_mobile_money()->exists()) {
                return [
                    'success' => false,
                    'message' => 'Impossible de supprimer ce moyen de paiement car il est utilisé dans des comptes mobile money existants',
                ];
            }

            if ($moyen->logo) {
                Storage::disk('public')->delete($moyen->logo);
            }

            $moyen->delete();

            \Log::info('Moyen de paiement supprimé', ['id' => $moyen->id, 'code' => $moyen->code]);

            return [
                'success' => true,
                'message' => 'Moyen de paiement supprimé avec succès',
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du moyen de paiement', [
                'id'    => $moyen->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ];
        }
    }

    public function basculerStatut(MoyenPaiement $moyen): array
    {
        try {
            $nouvelEtat = !$moyen->est_actif;
            $moyen->update(['est_actif' => $nouvelEtat]);

            $statut = $nouvelEtat ? 'activé' : 'désactivé';

            \Log::info("Moyen de paiement $statut", ['id' => $moyen->id, 'est_actif' => $nouvelEtat]);

            return [
                'success'   => true,
                'message'   => "Moyen de paiement $statut avec succès",
                'est_actif' => $nouvelEtat,
                'data'      => $moyen->fresh(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors du changement de statut du moyen de paiement', [
                'id'    => $moyen->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }

    public function definirParDefaut(MoyenPaiement $moyen): array
    {
        try {
            DB::beginTransaction();

            MoyenPaiement::where('par_defaut', true)->update(['par_defaut' => false]);
            $moyen->update(['par_defaut' => true]);

            DB::commit();

            \Log::info('Moyen de paiement défini par défaut', ['id' => $moyen->id]);

            return [
                'success' => true,
                'message' => "'{$moyen->libelle}' défini comme moyen de paiement par défaut",
                'data'    => $moyen->fresh(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Erreur lors de la définition du moyen de paiement par défaut', [
                'id'    => $moyen->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }
}
