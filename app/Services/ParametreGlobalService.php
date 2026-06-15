<?php

namespace App\Services;

use App\Models\ParametreGlobal;

class ParametreGlobalService{
    public function listerParametresGlobaux(array $filtres = []): array
    {
        try {
            $query = ParametreGlobal::query();

            if (isset($filtres['cle'])) {
                $query->where('cle', 'like', "%{$filtres['cle']}%");
            }

            if (isset($filtres['valeur'])) {
                $query->where('valeur', 'like', "%{$filtres['valeur']}%");
            }

            $parametres = $query->orderBy('cle')->get();

            return [
                'success' => true,
                'message' => 'Paramètres globaux récupérés avec succès',
                'data' => $parametres,
                'total' => $parametres->count(),
            ];
        } catch (\Exception $e) {
            \Log::error('Erreur lors du listage des paramètres globaux', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération des paramètres globaux : ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    public function obtenirParametreGlobal(string $id): array
    {
        try {
            $parametre = ParametreGlobal::find($id);

            if (!$parametre) {
                return [
                    'success' => false,
                    'message' => 'Paramètre global non trouvé',
                    'data' => null,
                ];
            }

            return [
                'success' => true,
                'message' => 'Paramètre global récupéré avec succès',
                'data' => $parametre,
            ];
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du paramètre global', ['id' => $id, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération du paramètre global : ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    public function creerParametreGlobal(array $data, ?string $adminId = null): array
    {
        try {
            $cle = mettre_en_majuscule($data['cle']);

            if (ParametreGlobal::where('cle', $cle)->exists()) {
                return [
                    'success' => false,
                    'message' => "Un paramètre global avec la clé '$cle' existe déjà",
                ];
            }

            $parametre = ParametreGlobal::create([
                'cle' => $cle,
                'valeur' => $data['valeur'],
                'description' => $data['description'] ?? null,
                'modifie_par' => $adminId,
            ]);

            \Log::info('Paramètre global créé', ['id' => $parametre->id, 'cle' => $parametre->cle]);

            return [
                'success' => true,
                'message' => 'Paramètre global créé avec succès',
                'data' => $parametre,
            ];
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du paramètre global', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la création du paramètre global : ' . $e->getMessage(),
            ];
        }
    }

    public function modifierParametreGlobal(ParametreGlobal $parametre, array $data, ?string $adminId = null): array
    {
        try {
            $champs = [];

            if (array_key_exists('cle', $data)) {
                $nouvelleCle = mettre_en_majuscule($data['cle']);
                $existe = ParametreGlobal::where('cle', $nouvelleCle)
                    ->where('id', '!=', $parametre->id)
                    ->exists();

                if ($existe) {
                    return [
                        'success' => false,
                        'message' => "La clé '$nouvelleCle' est déjà utilisée par un autre paramètre global",
                    ];
                }

                $champs['cle'] = $nouvelleCle;
            }

            if (array_key_exists('valeur', $data)) {
                $champs['valeur'] = $data['valeur'];
            }

            if (array_key_exists('description', $data)) {
                $champs['description'] = $data['description'];
            }

            if ($adminId !== null) {
                $champs['modifie_par'] = $adminId;
            }

            if (empty($champs)) {
                return [
                    'success' => false,
                    'message' => 'Aucune donnée à mettre à jour pour ce paramètre global',
                ];
            }

            $parametre->update($champs);

            \Log::info('Paramètre global modifié', ['id' => $parametre->id]);

            return [
                'success' => true,
                'message' => 'Paramètre global modifié avec succès',
                'data' => $parametre->fresh(),
            ];
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la modification du paramètre global', ['id' => $parametre->id, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la modification du paramètre global : ' . $e->getMessage(),
            ];
        }
    }

    public function supprimerParametreGlobal(ParametreGlobal $parametre): array
    {
        try {
            $parametre->delete();

            \Log::info('Paramètre global supprimé', ['id' => $parametre->id, 'cle' => $parametre->cle]);

            return [
                'success' => true,
                'message' => 'Paramètre global supprimé avec succès',
            ];
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du paramètre global', ['id' => $parametre->id, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression du paramètre global : ' . $e->getMessage(),
            ];
        }
    }
}