<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreMoyenPaiementRequest;
use App\Http\Requests\UpdateMoyenPaiementRequest;
use App\Models\MoyenPaiement;
use App\Services\AuditLogger;
use App\Services\MoyenPaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
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

            AuditLogger::log('MOYEN_PAIEMENT.CREATE', $request->user(), 'moyens_paiements', null,
                null, Arr::except($data, ['logo']));

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function update(MoyenPaiement $moyenPaiement, UpdateMoyenPaiementRequest $request): JsonResponse
    {
        try {
            $avant = $moyenPaiement->only(['nom', 'operateur', 'est_actif']);
            $data  = $request->validated();

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

            AuditLogger::log('MOYEN_PAIEMENT.UPDATE', $request->user(), 'moyens_paiements',
                (string) $moyenPaiement->id, $avant, Arr::except($data, ['logo']));

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function destroy(MoyenPaiement $moyenPaiement): JsonResponse
    {
        try {
            $avant    = $moyenPaiement->only(['nom', 'operateur']);
            $resultat = $this->moyenPaiementService->supprimerMoyenPaiement($moyenPaiement);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            AuditLogger::log('MOYEN_PAIEMENT.DELETE', request()->user(), 'moyens_paiements',
                (string) $moyenPaiement->id, $avant, null);

            return $this->sendResponse([], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function basculerStatut(MoyenPaiement $moyenPaiement): JsonResponse
    {
        try {
            $avant    = ['est_actif' => $moyenPaiement->est_actif];
            $resultat = $this->moyenPaiementService->basculerStatut($moyenPaiement);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            AuditLogger::log('MOYEN_PAIEMENT.TOGGLE', request()->user(), 'moyens_paiements',
                (string) $moyenPaiement->id, $avant, ['est_actif' => !$moyenPaiement->est_actif]);

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

            AuditLogger::log('MOYEN_PAIEMENT.SET_DEFAULT', request()->user(), 'moyens_paiements',
                (string) $moyenPaiement->id);

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
