<?php

namespace App\Services;

use App\Http\Resources\ConditionGeneraleresource;
use App\Models\Administrateur;
use App\Models\ConditionGenerale;
use App\Models\Role;

class AdminConditionGeneraleService
{
    public function afficherToutesLesConditionGenerales(array $params = [])
    {
        try {
            $query = ConditionGenerale::query();

            if (!empty($params['search'])) {
                $q = $params['search'];
                $query->where(function ($qb) use ($q) {
                    $qb->where('titre', 'like', "%{$q}%")
                       ->orWhere('description', 'like', "%{$q}%");
                });
            }

            if (!empty($params['est_active'])) {
                $query->where('est_active', $params['est_active']);
            }

            $perPage   = min((int) ($params['per_page'] ?? 20), 100);
            $page      = (int) ($params['page'] ?? 1);
            $paginated = $query->orderBy('titre')->paginate($perPage, ['*'], 'page', $page);

            return [
                'success' => true,
                'message' => 'Liste des conditions générales récupérée avec succès.',
                'data'    => ConditionGeneraleresource::collection($paginated->getCollection()),
                'meta'    => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];

        }
        
    }

    // Enregistrer une nouvelle condition générale
    public function enregistrerConditionGenerale(array $data)
    {
        try {
            $conditionGenerale = ConditionGenerale::create($data);
            return new ConditionGeneraleresource($conditionGenerale);

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];

        }
    }

    // Afficher une condition générale spécifique
    public function afficherConditionGenerale(ConditionGenerale $conditionGenerale)
    {
        return new ConditionGeneraleresource($conditionGenerale);
    }

    // Si un utilisateur clique sur l'app mobile "afficher les conditions générales", on peut utiliser cette méthode pour récupérer la condition générale active et non supprimée
    public function afficherConditionGeneraleActive()
    {
        $conditionGeneraleActive = ConditionGenerale::where('est_active', true)->first();
        return new ConditionGeneraleresource($conditionGeneraleActive);
    }

    // Mettre à jour une condition générale existante
    public function mettreAJourConditionGenerale(ConditionGenerale $conditionGenerale, array $data)
    {
        try {
            $conditionGenerale->update($data);
            return new ConditionGeneraleresource($conditionGenerale);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];

        }
    }

    // Supprimer une condition générale
    public function supprimerConditionGenerale(ConditionGenerale $conditionGenerale)
    {
        try {
            $conditionGenerale->delete();
            return response()->json(['message' => 'Condition générale supprimée avec succès.']);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];

        }
    }

}