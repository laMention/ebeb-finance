<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreCompteMobileMoneyRequest;
use App\Http\Resources\CompteMobileMoneyResource;
use App\Models\CompteMobileMoney;
use App\Services\CompteMobileMoneyService;
use Illuminate\Http\JsonResponse;

class CompteMobileMoneyController extends BaseController
{
    protected CompteMobileMoneyService $compteMobileMoneyService;

    public function __construct(CompteMobileMoneyService $compteMobileMoneyService)
    {
        $this->compteMobileMoneyService = $compteMobileMoneyService;
    }

    public function index(): JsonResponse
    {
        try {
            $user = auth()->user();
            $resultat = $this->compteMobileMoneyService->listerComptesUtilisateur($user);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse(
                CompteMobileMoneyResource::collection($resultat['data'])->response()->getData(true),
                $resultat['message']
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function store(StoreCompteMobileMoneyRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $resultat = $this->compteMobileMoneyService->creerCompte($user, $request->validated());

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse(
                new CompteMobileMoneyResource($resultat['data']),
                $resultat['message']
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function definirPrincipal(CompteMobileMoney $compteMobileMoney): JsonResponse
    {
        try {
            $user = auth()->user();
            $resultat = $this->compteMobileMoneyService->definirComptePrincipal($user, $compteMobileMoney);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse(
                new CompteMobileMoneyResource($resultat['data']),
                $resultat['message']
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
