<?php

namespace App\Http\Controllers\Apiv1\ControlPanel;

use App\Http\Controllers\BaseController;
use App\Http\Requests\ControlPanel\ChangerEtatSurfaceRequest;
use App\Services\PlateformeSurfaceStateService;

class PlatformSurfaceStateController extends BaseController
{
    public function __construct(private readonly PlateformeSurfaceStateService $service)
    {
    }

    public function index()
    {
        return $this->sendResponse($this->service->getTousLesEtats(), 'États des surfaces de la plateforme.');
    }

    public function update(ChangerEtatSurfaceRequest $request, string $surface)
    {
        try {
            $admin = $request->user();

            if (!$admin->isSuperAdmin()) {
                return $this->sendError("Accès réservé au Super Administrateur principal.", [], 403);
            }

            if (!in_array($surface, PlateformeSurfaceStateService::SURFACES, true)) {
                return $this->sendError('Surface invalide.', [], 422);
            }

            $data = $request->validated();

            $etat = $this->service->changerStatut(
                surface: $surface,
                statut:  $data['statut'],
                message: $data['message'] ?? null,
                admin:   $admin,
                ip:      $request->ip(),
            );

            return $this->sendResponse($etat, 'État de la surface mis à jour.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
