<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreMoyenPaiementRequest;
use App\Http\Requests\UpdateMoyenPaiementRequest;
use App\Models\MoyenPaiement;
use App\Services\MoyenPaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MoyenPaiementController extends BaseController
{
    protected MoyenPaiementService $moyenPaiementService;

    public function __construct(MoyenPaiementService $moyenPaiementService)
    {
        $this->moyenPaiementService = $moyenPaiementService;
    }

    public function index(): JsonResponse
    {
        try {
            $filtres = request()->only(['est_actif', 'operateur']);
            $resultat = $this->moyenPaiementService->listerMoyensPaiement($filtres);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function show(MoyenPaiement $moyenPaiement): JsonResponse
    {
        try {
            $resultat = $this->moyenPaiementService->obtenirMoyenPaiement($moyenPaiement->id);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 404);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function store(StoreMoyenPaiementRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo')->store('moyens-paiement', 'public');
            }

            $resultat = $this->moyenPaiementService->creerMoyenPaiement($data);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function update(MoyenPaiement $moyenPaiement, UpdateMoyenPaiementRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('logo')) {
                if ($moyenPaiement->logo) {
                    Storage::disk('public')->delete($moyenPaiement->logo);
                }
                $data['logo'] = $request->file('logo')->store('moyens-paiement', 'public');
            }

            $resultat = $this->moyenPaiementService->modifierMoyenPaiement($moyenPaiement, $data);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function destroy(MoyenPaiement $moyenPaiement): JsonResponse
    {
        try {
            $resultat = $this->moyenPaiementService->supprimerMoyenPaiement($moyenPaiement);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse([], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function basculerStatut(MoyenPaiement $moyenPaiement): JsonResponse
    {
        try {
            $resultat = $this->moyenPaiementService->basculerStatut($moyenPaiement);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function definirParDefaut(MoyenPaiement $moyenPaiement): JsonResponse
    {
        try {
            $resultat = $this->moyenPaiementService->definirParDefaut($moyenPaiement);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
