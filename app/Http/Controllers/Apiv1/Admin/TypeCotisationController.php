<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreTypeCotisationRequest;
use App\Http\Requests\UpdateTypeCotisationRequest;
use App\Models\TypeCotisation;
use App\Services\AuditLogger;
use App\Services\TypeCotisationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypeCotisationController extends BaseController
{
    protected TypeCotisationService $typeCotisationService;

    public function __construct(TypeCotisationService $typeCotisationService)
    {
        $this->typeCotisationService = $typeCotisationService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filtres = $request->only(['categorie', 'est_actif', 'est_obligatoire']);
            $resultat = $this->typeCotisationService->listerTypesCotisations($filtres);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function show(TypeCotisation $typeCotisation): JsonResponse
    {
        try {
            $resultat = $this->typeCotisationService->obtenirTypeCotisation($typeCotisation->id);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 404);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function store(StoreTypeCotisationRequest $request): JsonResponse
    {
        try {
            $resultat = $this->typeCotisationService->creerTypeCotisation($request->validated());

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            AuditLogger::log('TYPE_COTISATION.CREATE', $request->user(), 'types_cotisations', null, null, $request->validated());

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function update(TypeCotisation $typeCotisation, UpdateTypeCotisationRequest $request): JsonResponse
    {
        try {
            $avant    = $typeCotisation->only(['nom', 'montant', 'est_actif', 'est_obligatoire']);
            $resultat = $this->typeCotisationService->modifierTypeCotisation($typeCotisation, $request->validated());

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            AuditLogger::log('TYPE_COTISATION.UPDATE', $request->user(), 'types_cotisations',
                (string) $typeCotisation->id, $avant, $request->validated());

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function destroy(TypeCotisation $typeCotisation): JsonResponse
    {
        try {
            $avant    = $typeCotisation->only(['nom', 'montant']);
            $resultat = $this->typeCotisationService->supprimerTypeCotisation($typeCotisation);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            AuditLogger::log('TYPE_COTISATION.DELETE', request()->user(), 'types_cotisations',
                (string) $typeCotisation->id, $avant, null);

            return $this->sendResponse([], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function basculerStatut(TypeCotisation $typeCotisation): JsonResponse
    {
        try {
            $avant    = ['est_actif' => $typeCotisation->est_actif];
            $resultat = $this->typeCotisationService->basculerStatut($typeCotisation);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            AuditLogger::log('TYPE_COTISATION.TOGGLE', request()->user(), 'types_cotisations',
                (string) $typeCotisation->id, $avant, ['est_actif' => !$typeCotisation->est_actif]);

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
