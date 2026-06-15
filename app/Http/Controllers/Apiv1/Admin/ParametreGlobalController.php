<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
// use App\Http\Controllers\Controller;
use App\Http\Requests\StoreParametreGlobalRequest;
use App\Http\Requests\UpdateParametreGlobalRequest;
use App\Models\ParametreGlobal;
use App\Services\ParametreGlobalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParametreGlobalController extends BaseController
{
    protected ParametreGlobalService $parametreGlobalService;

    public function __construct(ParametreGlobalService $parametreGlobalService)
    {
        $this->parametreGlobalService = $parametreGlobalService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filtres = $request->only(['cle', 'valeur']);
            $resultat = $this->parametreGlobalService->listerParametresGlobaux($filtres);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            $this->throw($e);
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    public function show(ParametreGlobal $parametreGlobal): JsonResponse
    {
        try {
            $resultat = $this->parametreGlobalService->obtenirParametreGlobal($parametreGlobal->id);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 404);
            }

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            $this->throw($e);
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    public function store(StoreParametreGlobalRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $resultat = $this->parametreGlobalService->creerParametreGlobal($data, $request->user()->id);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            $this->throw($e);
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    public function update(ParametreGlobal $parametreGlobal, UpdateParametreGlobalRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $resultat = $this->parametreGlobalService->modifierParametreGlobal($parametreGlobal, $data, $request->user()->id);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            $this->throw($e);
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    public function destroy(ParametreGlobal $parametreGlobal): JsonResponse
    {
        try {
            $resultat = $this->parametreGlobalService->supprimerParametreGlobal($parametreGlobal);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse([], $resultat['message']);
        } catch (\Exception $e) {
            $this->throw($e);
            return $this->sendError($e->getMessage(), [], 500);
        }
    }
}
