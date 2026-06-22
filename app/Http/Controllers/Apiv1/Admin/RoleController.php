<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\SyncPermissionsRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Services\AuditLogger;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends BaseController
{
    public function __construct(private readonly RoleService $service) {}

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

    public function all(): JsonResponse
    {
        try {
            $result = $this->service->tousRoles();
            if (!$result['success']) return $this->sendError($result['message'], [], 500);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function show(Role $role): JsonResponse
    {
        try {
            $result = $this->service->afficher($role->id);
            if (!$result['success']) return $this->sendError($result['message'], [], 500);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            $result = $this->service->creer($request->validated());
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('ROLE.CREATE', $request->user(), 'roles', null, null, $request->validated());
            return $this->sendResponse($result['data'], $result['message'], 201);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        try {
            $avant  = $role->only(['name', 'display_name', 'description']);
            $result = $this->service->modifier($role, $request->validated());
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('ROLE.UPDATE', $request->user(), 'roles', (string) $role->id, $avant, $request->validated());
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function archive(Role $role): JsonResponse
    {
        try {
            $result = $this->service->archiver($role);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('ROLE.ARCHIVE', request()->user(), 'roles', (string) $role->id, ['name' => $role->name], null);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function restore(Role $role): JsonResponse
    {
        try {
            $result = $this->service->restaurer($role);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('ROLE.RESTORE', request()->user(), 'roles', (string) $role->id, null, ['name' => $role->name]);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function syncPermissions(SyncPermissionsRoleRequest $request, Role $role): JsonResponse
    {
        try {
            $result = $this->service->syncPermissions($role, $request->validated()['permission_ids']);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('ROLE.SYNC_PERMISSIONS', $request->user(), 'roles', (string) $role->id,
                null, ['permission_ids' => $request->validated()['permission_ids']]);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }
}
