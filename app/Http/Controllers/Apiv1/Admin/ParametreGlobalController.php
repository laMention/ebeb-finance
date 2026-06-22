<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreParametreGlobalRequest;
use App\Http\Requests\UpdateParametreGlobalRequest;
use App\Models\ParametreGlobal;
use App\Services\AuditLogger;
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

    // ─────────────────────────────────────────────────────────────────────────
    // Endpoints de configuration groupée (Single Source of Truth)
    // ─────────────────────────────────────────────────────────────────────────

    /** GET /parametres-globaux/config — retourne tous les paramètres structurés */
    public function config(): JsonResponse
    {
        return $this->sendResponse($this->parametreGlobalService->getTous(), 'Paramètres globaux.');
    }

    /** PUT /parametres-globaux/config — sauvegarde groupée de tous les paramètres */
    public function saveConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'TAUX_COMMISSION'             => 'nullable|numeric|min:0|max:100',
            'COTISATION_CNPS_MIN'         => 'nullable|integer|min:0',
            'COTISATION_AMU_MIN'          => 'nullable|integer|min:0',
            'OTP_DUREE_SECONDES'          => 'nullable|integer|min:30|max:3600',
            'OTP_TENTATIVES_MAX'          => 'nullable|integer|min:1|max:10',
            'OTP_REQUIS_KYC'              => 'nullable|boolean',
            'ESCROW_DELAI_ALERTE_HEURES'  => 'nullable|integer|min:1|max:720',
            'ALERTE_CRITIQUE_TEMPS_REEL'  => 'nullable|boolean',
            'SERVICE_WAVE'                => 'nullable|boolean',
            'SERVICE_ORANGE_MONEY'        => 'nullable|boolean',
            'SERVICE_MTN'                 => 'nullable|boolean',
            'SERVICE_MOOV'                => 'nullable|boolean',
            'SERVICE_REVERSEMENT_CNPS'    => 'nullable|boolean',
            'NOTIF_SMS'                   => 'nullable|boolean',
            'NOTIF_EMAIL'                 => 'nullable|boolean',
        ]);

        $avant  = $this->parametreGlobalService->getTous();
        $result = $this->parametreGlobalService->sauvegarderTous($validated, $request->user()->id);

        AuditLogger::log(
            'PARAMETRE.SAVE_ALL',
            $request->user(),
            'parametres_globaux',
            null,
            $avant,
            $validated
        );

        return $this->sendResponse($result, 'Paramètres enregistrés avec succès.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CRUD individuel (rétro-compatibilité)
    // ─────────────────────────────────────────────────────────────────────────

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
            $data     = $request->validated();
            $resultat = $this->parametreGlobalService->creerParametreGlobal($data, $request->user()->id);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            AuditLogger::log('PARAMETRE.CREATE', $request->user(), 'parametres_globaux', null, null, $data);

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            $this->throw($e);
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    public function update(ParametreGlobal $parametreGlobal, UpdateParametreGlobalRequest $request): JsonResponse
    {
        try {
            $avant    = $parametreGlobal->only(['cle', 'valeur']);
            $data     = $request->validated();
            $resultat = $this->parametreGlobalService->modifierParametreGlobal($parametreGlobal, $data, $request->user()->id);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            AuditLogger::log('PARAMETRE.UPDATE', $request->user(), 'parametres_globaux',
                (string) $parametreGlobal->id, $avant, $data);

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            $this->throw($e);
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    public function destroy(ParametreGlobal $parametreGlobal): JsonResponse
    {
        try {
            $avant    = $parametreGlobal->only(['cle', 'valeur']);
            $resultat = $this->parametreGlobalService->supprimerParametreGlobal($parametreGlobal);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            AuditLogger::log('PARAMETRE.DELETE', request()->user(), 'parametres_globaux',
                (string) $parametreGlobal->id, $avant, null);

            return $this->sendResponse([], $resultat['message']);
        } catch (\Exception $e) {
            $this->throw($e);
            return $this->sendError($e->getMessage(), [], 500);
        }
    }
}
