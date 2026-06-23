<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Alerte;
use App\Services\AlerteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlerteController extends BaseController
{
    public function __construct(private readonly AlerteService $service) {}

    public function compteurs(): JsonResponse
    {
        try {
            $result = $this->service->compteurs();
            if (!$result['success']) return $this->sendError($result['message'], [], 500);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

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

    public function show(Alerte $alerte): JsonResponse
    {
        try {
            $result = $this->service->afficher($alerte);
            if (!$result['success']) return $this->sendError($result['message'], [], 500);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function marquerLu(string $alerteId): JsonResponse
    {
        try {
            $result = $this->service->marquerLu($alerteId);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function marquerTousLus(): JsonResponse
    {
        try {
            $result = $this->service->marquerTousLus();
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            return $this->sendResponse(null, $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function archive(Alerte $alerte): JsonResponse
    {
        try {
            $result = $this->service->archiver($alerte);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            return $this->sendResponse(null, $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function restore(string $alerteId): JsonResponse
    {
        try {
            $result = $this->service->restaurer($alerteId);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }
}
