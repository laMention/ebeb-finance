<?php

namespace App\Services;

use App\Http\Resources\LogAuditResource;
use App\Models\LogAudit;
use Illuminate\Support\Facades\Response;

class LogAuditService
{
    public function lister(array $params): array
    {
        try {
            $query = LogAudit::query();

            if (!empty($params['avec_archives'])) {
                $query->withTrashed();
            } elseif (!empty($params['seulement_archives'])) {
                $query->onlyTrashed();
            }

            if (!empty($params['search'])) {
                $q = $params['search'];
                $query->where(function ($qb) use ($q) {
                    $qb->where('utilisateur', 'like', "%{$q}%")
                       ->orWhere('action', 'like', "%{$q}%")
                       ->orWhere('entite_cible', 'like', "%{$q}%")
                       ->orWhere('ip_adresse', 'like', "%{$q}%")
                       ->orWhere('entite_id', 'like', "%{$q}%");
                });
            }

            if (!empty($params['action'])) {
                $query->where('action', $params['action']);
            }

            if (!empty($params['module'])) {
                $query->where('entite_cible', $params['module']);
            }

            if (!empty($params['date_debut'])) {
                $query->whereDate('created_at', '>=', $params['date_debut']);
            }

            if (!empty($params['date_fin'])) {
                $query->whereDate('created_at', '<=', $params['date_fin']);
            }

            $perPage   = min((int) ($params['per_page'] ?? 30), 200);
            $page      = (int) ($params['page'] ?? 1);
            $paginated = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

            return [
                'success' => true,
                'message' => 'Journal des audits',
                'data'    => LogAuditResource::collection($paginated->getCollection()),
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

    public function afficher(LogAudit $log): array
    {
        try {
            return [
                'success' => true,
                'message' => 'Détail du log',
                'data'    => new LogAuditResource($log),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function modules(): array
    {
        try {
            $modules = LogAudit::withTrashed()
                ->whereNotNull('entite_cible')
                ->distinct()
                ->orderBy('entite_cible')
                ->pluck('entite_cible')
                ->toArray();
            return ['success' => true, 'message' => 'Modules', 'data' => $modules];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actions(): array
    {
        try {
            $actions = LogAudit::withTrashed()
                ->distinct()
                ->orderBy('action')
                ->pluck('action')
                ->toArray();
            return ['success' => true, 'message' => 'Actions', 'data' => $actions];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function archiver(LogAudit $log): array
    {
        try {
            $log->delete();
            return ['success' => true, 'message' => 'Log archivé.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function restaurer(string $logId): array
    {
        try {
            $log = LogAudit::withTrashed()->findOrFail($logId);
            if (!$log->trashed()) {
                return ['success' => false, 'message' => 'Ce log n\'est pas archivé.'];
            }
            $log->restore();
            return ['success' => true, 'message' => 'Log restauré.', 'data' => new LogAuditResource($log)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function exportCsv(array $params): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = LogAudit::query();

        if (!empty($params['avec_archives'])) $query->withTrashed();
        if (!empty($params['search'])) {
            $q = $params['search'];
            $query->where(fn($qb) =>
                $qb->where('utilisateur', 'like', "%{$q}%")
                   ->orWhere('action', 'like', "%{$q}%")
                   ->orWhere('entite_cible', 'like', "%{$q}%")
                   ->orWhere('ip_adresse', 'like', "%{$q}%")
            );
        }
        if (!empty($params['action']))     $query->where('action', $params['action']);
        if (!empty($params['module']))     $query->where('entite_cible', $params['module']);
        if (!empty($params['date_debut'])) $query->whereDate('created_at', '>=', $params['date_debut']);
        if (!empty($params['date_fin']))   $query->whereDate('created_at', '<=', $params['date_fin']);

        $logs = $query->orderByDesc('created_at')->limit(5000)->get();

        return Response::streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Administrateur', 'Action', 'Module', 'Entité ID', 'IP', 'Archivé']);
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->utilisateur,
                    $log->action,
                    $log->entite_cible,
                    $log->entite_id,
                    $log->ip_adresse,
                    $log->deleted_at ? 'Oui' : 'Non',
                ]);
            }
            fclose($handle);
        }, 'audit-logs-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment',
        ]);
    }
}
