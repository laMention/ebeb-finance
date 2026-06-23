<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Permission;
use App\Services\AuditLogger;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends BaseController
{
    public function __construct(private readonly PermissionService $service) {}

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

    public function parModule(): JsonResponse
    {
        try {
            $result = $this->service->listerParModule();
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

    public function store(StorePermissionRequest $request): JsonResponse
    {
        try {
            $result = $this->service->creer($request->validated());
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('PERMISSION.CREATE', $request->user(), 'permissions', null, null, $request->validated());
            return $this->sendResponse($result['data'], $result['message'], 201);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        try {
            $avant  = $permission->only(['name', 'display_name', 'module', 'description']);
            $result = $this->service->modifier($permission, $request->validated());
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('PERMISSION.UPDATE', $request->user(), 'permissions',
                (string) $permission->id, $avant, $request->validated());
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }
}
