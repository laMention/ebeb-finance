<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreTypeCotisationRequest;
use App\Http\Requests\UpdateTypeCotisationRequest;
use App\Models\TypeCotisation;
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

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function update(TypeCotisation $typeCotisation, UpdateTypeCotisationRequest $request): JsonResponse
    {
        try {
            $resultat = $this->typeCotisationService->modifierTypeCotisation($typeCotisation, $request->validated());

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function destroy(TypeCotisation $typeCotisation): JsonResponse
    {
        try {
            $resultat = $this->typeCotisationService->supprimerTypeCotisation($typeCotisation);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse([], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function basculerStatut(TypeCotisation $typeCotisation): JsonResponse
    {
        try {
            $resultat = $this->typeCotisationService->basculerStatut($typeCotisation);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
