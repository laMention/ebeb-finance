<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Services\AuditLogger;
use App\Services\SystemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemeController extends BaseController
{
    public function __construct(private SystemeService $systemeService) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Guard Super Admin
    // ─────────────────────────────────────────────────────────────────────────

    private function superAdminOnly(Request $request): void
    {
        if (!$request->user()?->isSuperAdmin()) {
            abort(403, 'Accès réservé au Super Admin.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Logs système
    // ─────────────────────────────────────────────────────────────────────────

    /** GET /systeme/logs/info */
    public function logsInfo(Request $request): JsonResponse
    {
        $this->superAdminOnly($request);

        return $this->sendResponse(
            $this->systemeService->infoFichierLog(),
            'Informations du fichier de log.'
        );
    }

    /** GET /systeme/logs */
    public function logsIndex(Request $request): JsonResponse
    {
        $this->superAdminOnly($request);

        $params = $request->only(['niveau', 'search', 'date_debut', 'date_fin', 'page', 'per_page']);
        $result = $this->systemeService->listerLogs($params);

        return $this->sendResponse($result, 'Logs récupérés.');
    }

    /** GET /systeme/logs/telecharger */
    public function logsDownload(Request $request): BinaryFileResponse
    {
        $this->superAdminOnly($request);

        AuditLogger::log('SYSTEM.LOG_DOWNLOAD', $request->user(), 'systeme');

        return response()->download(
            $this->systemeService->cheminLog(),
            'laravel_' . now()->format('Y-m-d') . '.log',
            ['Content-Type' => 'text/plain']
        );
    }

    /** DELETE /systeme/logs */
    public function logsClear(Request $request): JsonResponse
    {
        $this->superAdminOnly($request);

        try {
            $this->systemeService->viderLogs();

            return $this->sendResponse([], 'Logs système vidés avec succès.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sauvegardes BDD
    // ─────────────────────────────────────────────────────────────────────────

    /** GET /systeme/backups */
    public function backupsIndex(Request $request): JsonResponse
    {
        $this->superAdminOnly($request);

        return $this->sendResponse(
            $this->systemeService->listerSauvegardes(),
            'Sauvegardes récupérées.'
        );
    }

    /** POST /systeme/backups */
    public function backupCreate(Request $request): JsonResponse
    {
        $this->superAdminOnly($request);

        try {
            $debut  = microtime(true);
            $result = $this->systemeService->creerSauvegarde();
            $duree  = round((microtime(true) - $debut) * 1000);

            return $this->sendResponse(
                ['backup' => $result, 'duree_ms' => $duree],
                'Sauvegarde créée avec succès.'
            );
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    /** GET /systeme/backups/{filename}/telecharger */
    public function backupDownload(Request $request, string $filename): BinaryFileResponse
    {
        $this->superAdminOnly($request);

        $path = $this->systemeService->cheminSauvegarde($filename);

        AuditLogger::log('SYSTEM.BACKUP_DOWNLOAD', $request->user(), 'systeme', $filename);

        return response()->download($path, $filename, ['Content-Type' => 'application/octet-stream']);
    }

    /** DELETE /systeme/backups/{filename} */
    public function backupDelete(Request $request, string $filename): JsonResponse
    {
        $this->superAdminOnly($request);

        try {
            $this->systemeService->supprimerSauvegarde($filename);

            return $this->sendResponse([], 'Sauvegarde supprimée.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 500);
        }
    }
}
