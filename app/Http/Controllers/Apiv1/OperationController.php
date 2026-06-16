<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\OperationResource;
use App\Models\Operation;
use Illuminate\Http\Request;

class OperationController extends BaseController
{
    /**
     * Historique complet des transactions de l'utilisateur connecté.
     * Retourne TOUTES les opérations : paiements reçus, cotisations (CNPS, AMU, personnalisées),
     * prélèvements épargne, commissions, virements, reports, etc.
     *
     * Filtres optionnels :
     *  - per_page          (int, défaut 20, max 100)
     *  - type              (string)  : filtrer sur un seul type_operation
     *  - types[]           (array)   : filtrer sur plusieurs type_operation
     *  - sens              (CREDIT|DEBIT)
     *  - mois + annee      (int)     : opérations d'un mois donné
     *  - date_debut + date_fin (Y-m-d)
     */
    public function index(Request $request)
    {
        try {
            $query = Operation::where('user_id', $request->user()->id)
                ->with(['type_cotisation', 'objectif_epargne'])
                ->orderByDesc('date_operation')
                ->orderByDesc('created_at');

            if ($request->filled('type')) {
                $query->where('type_operation', $request->input('type'));
            }

            if ($request->has('types')) {
                $query->whereIn('type_operation', (array) $request->input('types'));
            }

            if ($request->filled('sens')) {
                $sens = strtoupper($request->input('sens'));
                $query->whereIn(
                    'type_operation',
                    $sens === 'CREDIT' ? Operation::TYPES_CREDIT : Operation::TYPES_DEBIT
                );
            }

            if ($request->filled('mois') && $request->filled('annee')) {
                $query->whereMonth('date_operation', (int) $request->input('mois'))
                      ->whereYear('date_operation', (int) $request->input('annee'));
            }

            if ($request->filled('date_debut')) {
                $query->whereDate('date_operation', '>=', $request->input('date_debut'));
            }

            if ($request->filled('date_fin')) {
                $query->whereDate('date_operation', '<=', $request->input('date_fin'));
            }

            $operations = $query->paginate(min((int) $request->input('per_page', 20), 100));

            return $this->sendResponse(
                OperationResource::collection($operations)->response()->getData(true),
                'Opérations récupérées avec succès.'
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Détail d'une opération avec ses sous-opérations.
     */
    public function show(Request $request, Operation $operation)
    {
        try {
            if ($operation->user_id !== $request->user()->id) {
                return $this->sendError('Accès non autorisé.', [], 403);
            }

            $operation->loadMissing([
                'type_cotisation',
                'objectif_epargne',
                'paiement_entrant',
                'sous_operations.type_cotisation',
                'sous_operations.objectif_epargne',
            ]);

            return $this->sendResponse(
                new OperationResource($operation),
                'Opération récupérée avec succès.'
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
