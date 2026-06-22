<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\Administrateur;
use App\Services\AdminGestionService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminGestionController extends BaseController
{
    public function __construct(private readonly AdminGestionService $service) {}

    public function dashboard(): JsonResponse
    {
        try {
            $result = $this->service->dashboard();
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

    public function show(Administrateur $admin): JsonResponse
    {
        try {
            $result = $this->service->afficher($admin);
            if (!$result['success']) return $this->sendError($result['message'], [], 500);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        try {
            $result = $this->service->creer($request->validated());
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('ADMIN.CREATE', $request->user(), 'administrateurs', $result['data']->id ?? null,
                null, $request->safe()->except('password'));
            return $this->sendResponse($result['data'], $result['message'], 201);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function update(UpdateAdminRequest $request, Administrateur $admin): JsonResponse
    {
        try {
            $avant  = $admin->only(['nom', 'prenom', 'email', 'telephone', 'ville', 'adresse', 'statut_compte']);
            $result = $this->service->modifier($admin, $request->validated());
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('ADMIN.UPDATE', $request->user(), 'administrateurs', $admin->id,
                $avant, $request->safe()->except('password'));
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function changerStatut(Request $request, Administrateur $admin): JsonResponse
    {
        try {
            $request->validate(['statut' => ['required', 'in:ACTIF,INACTIF']]);
            $avant  = ['statut_compte' => $admin->statut_compte];
            $result = $this->service->changerStatut($admin, $request->statut);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('ADMIN.' . ($request->statut === 'ACTIF' ? 'ACTIVATE' : 'DEACTIVATE'),
                $request->user(), 'administrateurs', $admin->id, $avant, ['statut_compte' => $request->statut]);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function archive(Administrateur $admin): JsonResponse
    {
        try {
            $result = $this->service->archiver($admin);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('ADMIN.ARCHIVE', request()->user(), 'administrateurs', $admin->id,
                ['email' => $admin->email], null);
            return $this->sendResponse(null, $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function restore(string $adminId): JsonResponse
    {
        try {
            $result = $this->service->restaurer($adminId);
            if (!$result['success']) return $this->sendError($result['message'], [], 422);
            AuditLogger::log('ADMIN.RESTORE', request()->user(), 'administrateurs', $adminId);
            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }
}
