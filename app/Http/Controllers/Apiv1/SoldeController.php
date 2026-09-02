<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Services\RecapitulatifService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SoldeController extends BaseController
{
    public function __construct(private readonly RecapitulatifService $service) {}

    /**
     * Soldes globaux du portefeuille (solde_principal, solde_epargne) — indépendants
     * de toute période, contrairement au récapitulatif mensuel.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $soldes = $this->service->soldesGlobaux($request->user());

            return $this->sendResponse($soldes, 'Soldes récupérés avec succès.');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
