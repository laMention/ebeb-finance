<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Models\LogAudit;
use App\Services\LogAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogAuditController extends BaseController
{
    public function __construct(private readonly LogAuditService $service) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->service->lister($request->all());
            if (!$result['success']) return $this->sendError($result['message'], [], 500);
            return $this->sendResponse([
                'data' => $result['data'],
                'meta' => $result['meta'],
            ], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function show(LogAudit $logAudit): JsonResponse
    {
        try {
            $result = $this->service->afficher($logAudit);
            if (!$result['success']) return $this->sendError($result['message'], [], 500);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function modules(): JsonResponse
    {
        try {
            $result = $this->service->modules();
            if (!$result['success']) return $this->sendError($result['message'], [], 500);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function actions(): JsonResponse
    {
        try {
            $result = $this->service->actions();
            if (!$result['success']) return $this->sendError($result['message'], [], 500);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function archive(LogAudit $logAudit): JsonResponse
    {
        try {
            $result = $this->service->archiver($logAudit);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            return $this->sendResponse(null, $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function restore(string $logId): JsonResponse
    {
        try {
            $result = $this->service->restaurer($logId);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->service->exportCsv($request->all());
    }
}
