<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\UpdateSeuilPrelevementRequest;
use App\Services\SeuilPrelevementService;
use Illuminate\Http\JsonResponse;

class SeuilPrelevementController extends BaseController
{
    public function __construct(
        private readonly SeuilPrelevementService $service
    ) {}

    public function show(): JsonResponse
    {
        try {
            $resultat = $this->service->obtenir();
            if (!$resultat['success']) return $this->sendError($resultat['message'], [], 500);
            return $this->sendResponse($resultat['data'], $resultat['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function update(UpdateSeuilPrelevementRequest $request): JsonResponse
    {
        try {
            $admin    = $request->user();
            $adminNom = "{$admin->prenom} {$admin->nom}";

            $resultat = $this->service->mettreAJour($request->validated(), $adminNom);

            if (!$resultat['success']) return $this->sendError($resultat['message'], [], 422);
            return $this->sendResponse($resultat['data'], $resultat['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }
}
