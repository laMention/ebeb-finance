<?php

namespace App\Services;

use App\Http\Resources\AlerteResource;
use App\Models\Alerte;

class AlerteService
{
    public function compteurs(): array
    {
        try {
            $nonLus = Alerte::where('est_lu', false);
            return [
                'success' => true,
                'message' => 'Compteurs des alertes',
                'data'    => [
                    'total_non_lus'      => (clone $nonLus)->count(),
                    'critique_non_lus'   => (clone $nonLus)->where('niveau', 'CRITIQUE')->count(),
                    'avertissement_non_lus' => (clone $nonLus)->where('niveau', 'AVERTISSEMENT')->count(),
                    'info_non_lus'       => (clone $nonLus)->where('niveau', 'INFO')->count(),
                    'succes_non_lus'     => (clone $nonLus)->where('niveau', 'SUCCES')->count(),
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function lister(array $params): array
    {
        try {
            $query = Alerte::query();

            if (!empty($params['avec_archives'])) {
                $query->withTrashed();
            } elseif (!empty($params['seulement_archives'])) {
                $query->onlyTrashed();
            }

            if (!empty($params['search'])) {
                $q = $params['search'];
                $query->where(function ($qb) use ($q) {
                    $qb->where('titre', 'like', "%{$q}%")
                       ->orWhere('description', 'like', "%{$q}%")
                       ->orWhere('type_alerte', 'like', "%{$q}%");
                });
            }

            if (!empty($params['niveau'])) {
                $query->where('niveau', strtoupper($params['niveau']));
            }

            if (!empty($params['type_alerte'])) {
                $query->where('type_alerte', strtoupper($params['type_alerte']));
            }

            if (isset($params['est_lu']) && $params['est_lu'] !== '') {
                $query->where('est_lu', (bool) $params['est_lu']);
            }

            if (!empty($params['date_debut'])) {
                $query->whereDate('created_at', '>=', $params['date_debut']);
            }

            if (!empty($params['date_fin'])) {
                $query->whereDate('created_at', '<=', $params['date_fin']);
            }

            // Critiques en premier, puis par date décroissante
            $query->orderByRaw("CASE niveau WHEN 'CRITIQUE' THEN 0 WHEN 'AVERTISSEMENT' THEN 1 WHEN 'INFO' THEN 2 WHEN 'SUCCES' THEN 3 ELSE 4 END")
                  ->orderByDesc('created_at');

            $perPage   = min((int) ($params['per_page'] ?? 30), 100);
            $page      = (int) ($params['page'] ?? 1);
            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            return [
                'success' => true,
                'message' => 'Alertes récupérées.',
                'data'    => AlerteResource::collection($paginated->getCollection()),
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

    public function afficher(Alerte $alerte): array
    {
        try {
            return [
                'success' => true,
                'message' => 'Détail de l\'alerte.',
                'data'    => new AlerteResource($alerte),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function marquerLu(string $alerteId): array
    {
        try {
            $alerte = Alerte::findOrFail($alerteId);
            if (!$alerte->est_lu) {
                $alerte->update(['est_lu' => true, 'date_lecture' => now()]);
            }
            return ['success' => true, 'message' => 'Alerte marquée comme lue.', 'data' => new AlerteResource($alerte->refresh())];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function marquerTousLus(): array
    {
        try {
            Alerte::where('est_lu', false)->update(['est_lu' => true, 'date_lecture' => now()]);
            return ['success' => true, 'message' => 'Toutes les alertes marquées comme lues.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function archiver(Alerte $alerte): array
    {
        try {
            $alerte->delete();
            return ['success' => true, 'message' => 'Alerte archivée.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function restaurer(string $alerteId): array
    {
        try {
            $alerte = Alerte::withTrashed()->findOrFail($alerteId);
            if (!$alerte->trashed()) {
                return ['success' => false, 'message' => 'Cette alerte n\'est pas archivée.'];
            }
            $alerte->restore();
            return ['success' => true, 'message' => 'Alerte restaurée.', 'data' => new AlerteResource($alerte)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
