<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\RembourserOperationRequest;
use App\Http\Resources\OperationAdminResource;
use App\Models\Operation;
use App\Services\OperationService;
use App\Services\RemboursementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends BaseController
{
    protected OperationService $operationService;

    public function __construct(
        OperationService $operationService,
        private RemboursementService $remboursementService,
    ) {
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

    /**
     * Contrôles d'éligibilité + récapitulatif avant remboursement.
     * GET /administration/panel-admin/transactions/{operation}/eligibilite-remboursement
     */
    public function eligibiliteRemboursement(Operation $operation): JsonResponse
    {
        try {
            $operation = $operation->load(['user:id,nom,prenom,telephone,reference', 'type_cotisation:id,libelle,code']);
            $eligibilite = $this->remboursementService->verifierEligibilite($operation);

            return $this->sendResponse([
                'eligible'       => $eligibilite['eligible'],
                'raison'         => $eligibilite['raison'],
                'avertissements' => $eligibilite['avertissements'],
                'montant_max'    => $eligibilite['montant_max'],
                'operation'      => new OperationAdminResource($operation),
            ], 'Éligibilité au remboursement vérifiée.');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Rembourse un prélèvement erroné à l'utilisateur.
     * POST /administration/panel-admin/transactions/{operation}/rembourser
     */
    public function rembourser(RembourserOperationRequest $request, Operation $operation): JsonResponse
    {
        try {
            $remboursement = $this->remboursementService->rembourser(
                $operation,
                $request->user(),
                $request->validated('motif'),
                $request->validated('montant'),
            );

            return $this->sendResponse([
                'remboursement_id'           => $remboursement->id,
                'operation_remboursement_id' => $remboursement->operation_remboursement_id,
                'montant_rembourse'          => (float) $remboursement->montant_rembourse,
            ], 'Remboursement effectué avec succès.');

        } catch (\RuntimeException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
