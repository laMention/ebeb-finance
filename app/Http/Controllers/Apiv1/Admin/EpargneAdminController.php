<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\ObjectifEpargneAdminResource;
use App\Models\ObjectifEpargne;
use App\Services\EpargneAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EpargneAdminController extends BaseController
{
    protected EpargneAdminService $service;

    public function __construct(EpargneAdminService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /administration/panel-admin/epargne/kpis
     * KPIs globaux : épargne totale, objectifs actifs, taux de complétion.
     */
    public function kpis(): JsonResponse
    {
        try {
            return $this->sendResponse($this->service->kpis(), 'KPIs épargne récupérés.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * GET /administration/panel-admin/epargne
     * Liste paginée des objectifs d'épargne avec filtres.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params    = $request->only(['recherche', 'statut', 'progression', 'date_debut', 'date_fin', 'page', 'per_page']);
            $paginated = $this->service->lister($params);

            return $this->sendResponse([
                'objectifs' => ObjectifEpargneAdminResource::collection($paginated->getCollection()),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                    'from'         => $paginated->firstItem(),
                    'to'           => $paginated->lastItem(),
                ],
            ], 'Objectifs d\'épargne récupérés avec succès.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * GET /administration/panel-admin/epargne/{objectifEpargne}
     * Détail complet d'un objectif avec l'historique des opérations.
     */
    public function show(ObjectifEpargne $objectifEpargne): JsonResponse
    {
        try {
            $objectifEpargne = $this->service->obtenirDetail($objectifEpargne);

            return $this->sendResponse(
                ['objectif' => new ObjectifEpargneAdminResource($objectifEpargne)],
                'Objectif d\'épargne récupéré avec succès.'
            );
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
