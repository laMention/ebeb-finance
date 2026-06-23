<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Operation;
use App\Services\RepartitionAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepartitionAdminController extends BaseController
{
    public function __construct(
        private readonly RepartitionAdminService $service
    ) {}

    public function dashboard(): JsonResponse
    {
        try {
            $resultat = $this->service->dashboard();
            if (!$resultat['success']) return $this->sendError($resultat['message'], [], 500);
            return $this->sendResponse($resultat['data'], $resultat['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $resultat = $this->service->lister($request->only([
                'search', 'statut', 'date_debut', 'date_fin', 'per_page', 'page',
            ]));
            if (!$resultat['success']) return $this->sendError($resultat['message'], [], 400);
            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function show(Operation $operation): JsonResponse
    {
        try {
            $resultat = $this->service->afficher($operation);
            if (!$resultat['success']) return $this->sendError($resultat['message'], [], 404);
            return $this->sendResponse($resultat['data'], $resultat['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function regles(): JsonResponse
    {
        try {
            $resultat = $this->service->regles();
            if (!$resultat['success']) return $this->sendError($resultat['message'], [], 500);
            return $this->sendResponse($resultat['data'], $resultat['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }
}
