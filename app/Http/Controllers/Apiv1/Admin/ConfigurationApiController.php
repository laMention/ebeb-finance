<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreConfigurationApiRequest;
use App\Http\Requests\UpdateConfigurationApiRequest;
use App\Models\ConfigurationApiOperateur;
use App\Services\ConfigurationApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigurationApiController extends BaseController
{
    public function __construct(
        private readonly ConfigurationApiService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $resultat = $this->service->lister($request->only([
                'search', 'moyen_paiement_id', 'environnement', 'est_actif', 'per_page', 'page',
            ]));

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function show(ConfigurationApiOperateur $configurationApiOperateur): JsonResponse
    {
        try {
            return $this->sendResponse(
                ['success' => true, 'data' => new \App\Http\Resources\ConfigurationApiResource($configurationApiOperateur)],
                'Configuration API récupérée avec succès'
            );
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function store(StoreConfigurationApiRequest $request): JsonResponse
    {
        try {
            $resultat = $this->service->creer($request->validated());

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function update(
        ConfigurationApiOperateur $configurationApiOperateur,
        UpdateConfigurationApiRequest $request
    ): JsonResponse {
        try {
            $resultat = $this->service->modifier($configurationApiOperateur, $request->validated());

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function destroy(ConfigurationApiOperateur $configurationApiOperateur): JsonResponse
    {
        try {
            $resultat = $this->service->supprimer($configurationApiOperateur);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse([], $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function basculerStatut(ConfigurationApiOperateur $configurationApiOperateur): JsonResponse
    {
        try {
            $resultat = $this->service->basculerStatut($configurationApiOperateur);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function testerConnexion(ConfigurationApiOperateur $configurationApiOperateur): JsonResponse
    {
        try {
            $resultat = $this->service->testerConnexion($configurationApiOperateur);
            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function testerWebhook(ConfigurationApiOperateur $configurationApiOperateur): JsonResponse
    {
        try {
            $resultat = $this->service->testerWebhook($configurationApiOperateur);
            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
