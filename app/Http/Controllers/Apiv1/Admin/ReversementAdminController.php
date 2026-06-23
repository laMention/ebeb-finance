<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Reversement;
use App\Services\AuditLogger;
use App\Services\ReversementAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReversementAdminController extends BaseController
{
    public function __construct(
        private readonly ReversementAdminService $service
    ) {}

    public function dashboard(): JsonResponse
    {
        try {
            $resultat = $this->service->dashboard();
            if (!$resultat['success']) return $this->sendError($resultat['message'], [], 500);
            return $this->sendResponse($resultat['data'], $resultat['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $resultat = $this->service->lister($request->only([
                'search', 'partenaire_id', 'statut', 'mois', 'annee',
                'date_debut', 'date_fin', 'per_page', 'page',
            ]));
            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function show(Reversement $reversement): JsonResponse
    {
        try {
            $resultat = $this->service->afficher($reversement);
            if (!$resultat['success']) return $this->sendError($resultat['message'], [], 404);
            return $this->sendResponse($resultat['data'], $resultat['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function calculerDisponible(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'partenaire_id' => 'required|exists:partenaires_financiers,id',
                'periode_debut' => 'nullable|date',
                'periode_fin'   => 'nullable|date|after_or_equal:periode_debut',
            ]);

            $resultat = $this->service->calculerDisponible($request->only([
                'partenaire_id', 'periode_debut', 'periode_fin',
            ]));
            if (!$resultat['success']) return $this->sendError($resultat['message'], [], 400);
            return $this->sendResponse($resultat['data'], $resultat['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'partenaire_id' => 'required|exists:partenaires_financiers,id',
                'periode_debut' => 'nullable|date',
                'periode_fin'   => 'nullable|date|after_or_equal:periode_debut',
            ]);

            $admin    = $request->user();
            $resultat = $this->service->creer($request->only([
                'partenaire_id', 'periode_debut', 'periode_fin',
            ]), $admin);

            if (!$resultat['success']) return $this->sendError($resultat['message'], [], 422);
            AuditLogger::log('REVERSEMENT.CREATE', $admin, 'reversements', null,
                null, $request->only(['partenaire_id', 'periode_debut', 'periode_fin']));
            return $this->sendResponse($resultat['data'], $resultat['message'], 201);
        } catch (\Exception $e) { return $this->throw($e); }
    }

    public function annuler(Request $request, Reversement $reversement): JsonResponse
    {
        try {
            $request->validate([
                'motif_annulation' => 'required|string|max:1000',
            ]);

            $admin    = $request->user();
            $avant    = ['statut' => $reversement->statut];
            $resultat = $this->service->annuler($reversement, $request->only(['motif_annulation']), $admin);

            if (!$resultat['success']) return $this->sendError($resultat['message'], [], 422);
            AuditLogger::log('REVERSEMENT.CANCEL', $admin, 'reversements', (string) $reversement->id,
                $avant, $request->only(['motif_annulation']));
            return $this->sendResponse($resultat['data'], $resultat['message']);
        } catch (\Exception $e) { return $this->throw($e); }
    }
}
