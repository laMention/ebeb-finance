<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\OperationAdminResource;
use App\Models\Operation;
use App\Services\OperationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends BaseController
{
    protected OperationService $operationService;

    public function __construct(OperationService $operationService)
    {
        $this->operationService = $operationService;
    }

    /**
     * Liste paginée de toutes les opérations (admin global).
     * GET /administration/panel-admin/transactions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'recherche', 'type_operation', 'statut', 'operateur',
                'date_debut', 'date_fin', 'page', 'per_page',
            ]);

            $paginated = $this->operationService->listerOperations($params);

            return $this->sendResponse([
                'operations' => OperationAdminResource::collection($paginated->getCollection()),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                    'from'         => $paginated->firstItem(),
                    'to'           => $paginated->lastItem(),
                ],
            ], 'Opérations récupérées avec succès.');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Détail complet d'une opération + paiement entrant lié.
     * GET /administration/panel-admin/transactions/{operation}
     *
     * Les relations sous_operations et operation_parent sont chargées ici
     * et apparaissent automatiquement via whenLoaded() dans la Resource.
     */
    public function show(Operation $operation): JsonResponse
    {
        try {
            $operation = $this->operationService->obtenirOperation($operation);

            return $this->sendResponse(
                ['operation' => new OperationAdminResource($operation)],
                'Opération récupérée avec succès.'
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
