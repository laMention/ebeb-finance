<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Models\CompteMobileMoney;
use App\Services\MobileMoneyAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileMoneyAdminController extends BaseController
{
    public function __construct(
        private readonly MobileMoneyAdminService $service
    ) {}

    public function dashboard(): JsonResponse
    {
        try {
            $resultat = $this->service->dashboard();

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 500);
            }

            return $this->sendResponse($resultat['data'], $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $resultat = $this->service->lister($request->only([
                'search', 'moyen_paiement_id', 'operateur', 'statut',
                'est_principal', 'date_debut', 'date_fin', 'per_page', 'page',
            ]));

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function show(CompteMobileMoney $compteMobileMoney): JsonResponse
    {
        try {
            $resultat = $this->service->afficher($compteMobileMoney);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 404);
            }

            return $this->sendResponse($resultat['data'], $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function basculerStatut(CompteMobileMoney $compteMobileMoney): JsonResponse
    {
        try {
            $resultat = $this->service->basculerStatut($compteMobileMoney);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
