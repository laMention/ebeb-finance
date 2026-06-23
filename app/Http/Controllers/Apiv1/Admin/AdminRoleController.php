<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\AssignerPermissionsAdminRequest;
use App\Http\Requests\AssignerRoleAdminRequest;
use App\Models\Administrateur;
use App\Services\AdminRoleService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRoleController extends BaseController
{
    public function __construct(private readonly AdminRoleService $service) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->service->listerAdmins($request->all());
            if (!$result['success']) return $this->sendError($result['message'], [], 500);
            return $this->sendResponse([
                'data' => $result['data'],
                'meta' => $result['meta'],
            ], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function show(Administrateur $admin): JsonResponse
    {
        try {
            $result = $this->service->afficherAdmin($admin);
            if (!$result['success']) return $this->sendError($result['message'], [], 500);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function assignerRole(AssignerRoleAdminRequest $request, Administrateur $admin): JsonResponse
    {
        try {
            $result = $this->service->assignerRole($admin, $request->validated());
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('RBAC.ASSIGN_ROLE', $request->user(), 'administrateurs', $admin->id,
                null, $request->validated());
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function retirerRole(Administrateur $admin): JsonResponse
    {
        try {
            $result = $this->service->retirerRole($admin);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('RBAC.REMOVE_ROLE', request()->user(), 'administrateurs', $admin->id);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function assignerPermissions(AssignerPermissionsAdminRequest $request, Administrateur $admin): JsonResponse
    {
        try {
            $result = $this->service->assignerPermissionsDirectes($admin, $request->validated());
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('RBAC.ASSIGN_PERMISSIONS', $request->user(), 'administrateurs', $admin->id,
                null, $request->validated());
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function retirerPermissions(Administrateur $admin): JsonResponse
    {
        try {
            $result = $this->service->retirerPermissionsDirectes($admin);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('RBAC.REMOVE_PERMISSIONS', request()->user(), 'administrateurs', $admin->id);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }
}
