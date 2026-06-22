<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Services\AuditLogger;
use App\Services\NotificationConfigService;
use App\Services\NotificationLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationConfigController extends BaseController
{
    public function __construct(
        private NotificationConfigService $configService,
        private NotificationLogService    $logService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Configurations
    // ─────────────────────────────────────────────────────────────────────────

    /** GET /notification-config */
    public function index(): JsonResponse
    {
        return $this->sendResponse($this->configService->tous(), 'Configurations des canaux.');
    }

    /** GET /notification-config/{canal} */
    public function show(string $canal): JsonResponse
    {
        return $this->sendResponse($this->configService->getParCanal($canal), 'Configuration du canal.');
    }

    /** PUT /notification-config/{canal} */
    public function update(Request $request, string $canal): JsonResponse
    {
        $validated = $request->validate([
            'fournisseur'               => 'nullable|string|max:100',
            'configuration'             => 'nullable|array',
            'configuration.host'        => 'nullable|string|max:255',
            'configuration.port'        => 'nullable|integer|min:1|max:65535',
            'configuration.username'    => 'nullable|string|max:255',
            'configuration.password'    => 'nullable|string|max:500',
            'configuration.encryption'  => 'nullable|in:tls,ssl,none',
            'configuration.from_address'=> 'nullable|email|max:255',
            'configuration.from_name'   => 'nullable|string|max:100',
            'configuration.api_url'     => 'nullable|url|max:255',
            'configuration.api_key'     => 'nullable|string|max:500',
            'configuration.api_secret'  => 'nullable|string|max:500',
            'configuration.sender_id'   => 'nullable|string|max:20',
            'configuration.project_id'  => 'nullable|string|max:255',
            'configuration.provider'    => 'nullable|string|max:100',
        ]);

        $result = $this->configService->sauvegarder($canal, $validated);

        AuditLogger::log(
            'NOTIF.CONFIG_UPDATE',
            $request->user(),
            'notification_config',
            null,
            null,
            ['canal' => strtoupper($canal)]
        );

        return $this->sendResponse($result, 'Configuration mise à jour.');
    }

    /** PATCH /notification-config/{canal}/statut */
    public function basculerStatut(Request $request, string $canal): JsonResponse
    {
        $result = $this->configService->basculerStatut($canal);

        AuditLogger::log(
            'NOTIF.CANAL_TOGGLE',
            $request->user(),
            'notification_config',
            null,
            null,
            ['canal' => strtoupper($canal), 'est_actif' => $result['est_actif']]
        );

        return $this->sendResponse($result, $result['est_actif'] ? 'Canal activé.' : 'Canal désactivé.');
    }

    /** POST /notification-config/{canal}/tester */
    public function testerEnvoi(Request $request, string $canal): JsonResponse
    {
        $result = $this->configService->testerEnvoi($canal, $request->user());

        AuditLogger::log(
            'NOTIF.TEST_SEND',
            $request->user(),
            'notification_config',
            null,
            null,
            ['canal' => strtoupper($canal), 'success' => $result['success']]
        );

        return $this->sendResponse($result, $result['message'] ?? 'Test effectué.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Logs / Historique
    // ─────────────────────────────────────────────────────────────────────────

    /** GET /notification-logs */
    public function logs(Request $request): JsonResponse
    {
        $params = $request->only(['canal', 'statut', 'type_notification', 'search', 'date_debut', 'date_fin', 'page', 'per_page']);
        $result = $this->logService->lister($params);

        return $this->sendResponse($result, 'Historique des notifications.');
    }

    /** GET /notification-logs/compteurs */
    public function logsCompteurs(): JsonResponse
    {
        return $this->sendResponse($this->logService->compteurs(), 'Compteurs notifications.');
    }

    /** POST /notification-logs/{id}/reessayer */
    public function reessayer(Request $request, string $id): JsonResponse
    {
        $result = $this->logService->reessayer($id);

        if ($result['success']) {
            AuditLogger::log('NOTIF.RETRY', $request->user(), 'notification_log', $id);
        }

        return $this->sendResponse($result, $result['message'] ?? 'Tentative de renvoi.');
    }
}
