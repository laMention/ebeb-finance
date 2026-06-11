<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreTypeCotisationPersonnaliseeRequest;
use App\Http\Requests\UpdateTypeCotisationPersonnaliseeRequest;
use App\Models\TypeCotisation;
use App\Services\TypeCotisationPersonnaliseeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypeCotisationPersonnaliseeController extends BaseController
{
    protected TypeCotisationPersonnaliseeService $service;

    public function __construct(TypeCotisationPersonnaliseeService $service)
    {
        $this->service = $service;
    }

    /**
     * Lister les types de cotisations personnalisés de l'utilisateur.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $resultat = $this->service->listerTypesPersonnalises($userId);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Afficher un type de cotisation personnalisé.
     */
    public function show(TypeCotisation $typeCotisation, Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $resultat = $this->service->obtenirTypePersonnalise($typeCotisation->id, $userId);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 404);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Créer un type de cotisation personnalisé.
     */
    public function store(StoreTypeCotisationPersonnaliseeRequest $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $resultat = $this->service->creerTypePersonnalise($userId, $request->validated());

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Modifier un type de cotisation personnalisé.
     */
    public function update(TypeCotisation $typeCotisation, UpdateTypeCotisationPersonnaliseeRequest $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $resultat = $this->service->modifierTypePersonnalise($typeCotisation, $userId, $request->validated());

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], $resultat['message'] === 'Accès non autorisé' ? 403 : 422);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Supprimer un type de cotisation personnalisé.
     */
    public function destroy(TypeCotisation $typeCotisation, Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $resultat = $this->service->supprimerTypePersonnalise($typeCotisation, $userId);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], $resultat['message'] === 'Accès non autorisé' ? 403 : 422);
            }

            return $this->sendResponse([], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
