<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\PartenaireFinancierResource;
use App\Models\PartenairesFinancier;
use App\Services\PartenaireFinancierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartenaireFinancierController extends BaseController
{
    protected PartenaireFinancierService $service;

    public function __construct(PartenaireFinancierService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /administration/panel-admin/configurations/partenaires-financiers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only(['recherche', 'type', 'page', 'per_page']);
            $data   = $this->service->lister($params);

            return $this->sendResponse($data, 'Partenaires financiers récupérés avec succès.');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * POST /administration/panel-admin/configurations/partenaires-financiers
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nom'  => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:partenaires_financiers,code',
                'type' => 'required|string|max:100',
            ]);

            $partenaire = $this->service->creer($validated);

            return $this->sendResponse(
                ['partenaire' => new PartenaireFinancierResource($partenaire)],
                'Partenaire financier créé avec succès.'
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Données invalides.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * GET /administration/panel-admin/configurations/partenaires-financiers/{partenaireFinancier}
     */
    public function show(PartenairesFinancier $partenaireFinancier): JsonResponse
    {
        try {
            $partenaireFinancier->loadCount('reversements');

            return $this->sendResponse(
                ['partenaire' => new PartenaireFinancierResource($partenaireFinancier)],
                'Partenaire financier récupéré avec succès.'
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * PUT /administration/panel-admin/configurations/partenaires-financiers/{partenaireFinancier}
     */
    public function update(Request $request, PartenairesFinancier $partenaireFinancier): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nom'  => 'sometimes|required|string|max:255',
                'code' => 'sometimes|required|string|max:50|unique:partenaires_financiers,code,' . $partenaireFinancier->id,
                'type' => 'sometimes|required|string|max:100',
            ]);

            $partenaire = $this->service->modifier($partenaireFinancier, $validated);

            return $this->sendResponse(
                ['partenaire' => new PartenaireFinancierResource($partenaire)],
                'Partenaire financier modifié avec succès.'
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Données invalides.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * DELETE /administration/panel-admin/configurations/partenaires-financiers/{partenaireFinancier}
     */
    public function destroy(PartenairesFinancier $partenaireFinancier): JsonResponse
    {
        try {
            $this->service->supprimer($partenaireFinancier);

            return $this->sendResponse([], 'Partenaire financier supprimé avec succès.');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
