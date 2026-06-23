<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\CotisationAdminResource;
use App\Services\CotisationAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CotisationAdminController extends BaseController
{
    protected CotisationAdminService $service;

    public function __construct(CotisationAdminService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /administration/panel-admin/cotisations/kpis
     * KPIs globaux : montants collectés par type + conformité.
     */
    public function kpis(): JsonResponse
    {
        try {
            return $this->sendResponse($this->service->kpis(), 'KPIs cotisations récupérés.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * GET /administration/panel-admin/cotisations/evolution?annee=2026
     * Évolution mensuelle des montants collectés pour une année donnée.
     */
    public function evolutionMensuelle(Request $request): JsonResponse
    {
        try {
            $annee = (int) $request->input('annee', now()->year);

            return $this->sendResponse(
                ['evolution' => $this->service->evolutionMensuelle($annee)],
                'Évolution mensuelle récupérée.'
            );
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * GET /administration/panel-admin/cotisations/par-type
     * Agrégat par type de cotisation (total, nb opérations, nb utilisateurs).
     */
    public function parType(): JsonResponse
    {
        try {
            return $this->sendResponse(
                ['types' => $this->service->parType()],
                'Cotisations par type récupérées.'
            );
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * GET /administration/panel-admin/cotisations
     * Liste paginée des cotisations avec filtres.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params    = $request->only(['recherche', 'statut', 'annee', 'mois', 'page', 'per_page']);
            $paginated = $this->service->lister($params);

            return $this->sendResponse([
                'cotisations' => CotisationAdminResource::collection($paginated->getCollection()),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                    'from'         => $paginated->firstItem(),
                    'to'           => $paginated->lastItem(),
                ],
            ], 'Cotisations récupérées avec succès.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
