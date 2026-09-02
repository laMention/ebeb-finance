<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\PartenaireCompteDestinationResource;
use App\Models\PartenaireCompteDestination;
use App\Models\PartenairesFinancier;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartenaireCompteDestinationController extends BaseController
{
    /**
     * GET /administration/panel-admin/configurations/partenaires-financiers/{partenaireFinancier}/comptes-destination
     */
    public function index(PartenairesFinancier $partenaireFinancier): JsonResponse
    {
        try {
            $comptes = $partenaireFinancier->comptesDestination()->orderBy('libelle')->get();

            return $this->sendResponse(
                ['comptes_destination' => PartenaireCompteDestinationResource::collection($comptes)],
                'Comptes de destination récupérés avec succès.'
            );
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * POST /administration/panel-admin/configurations/partenaires-financiers/{partenaireFinancier}/comptes-destination
     */
    public function store(Request $request, PartenairesFinancier $partenaireFinancier): JsonResponse
    {
        try {
            $validated = $request->validate([
                'libelle'          => 'required|string|max:255',
                'type_compte'      => 'required|in:BANQUE,MOBILE_MONEY,AUTRE',
                'numero_compte'    => 'required|string|max:100',
                'banque_operateur' => 'nullable|string|max:255',
                'est_actif'        => 'sometimes|boolean',
            ]);

            $compte = $partenaireFinancier->comptesDestination()->create($validated);
            AuditLogger::log('PARTENAIRE_COMPTE.CREATE', $request->user(), 'partenaire_comptes_destination',
                (string) $compte->id, null, $validated);

            return $this->sendResponse(
                ['compte_destination' => new PartenaireCompteDestinationResource($compte)],
                'Compte de destination créé avec succès.'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Données invalides.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * PUT /administration/panel-admin/configurations/partenaires-financiers/{partenaireFinancier}/comptes-destination/{compteDestination}
     */
    public function update(Request $request, PartenairesFinancier $partenaireFinancier, PartenaireCompteDestination $compteDestination): JsonResponse
    {
        try {
            $validated = $request->validate([
                'libelle'          => 'sometimes|required|string|max:255',
                'type_compte'      => 'sometimes|required|in:BANQUE,MOBILE_MONEY,AUTRE',
                'numero_compte'    => 'sometimes|required|string|max:100',
                'banque_operateur' => 'nullable|string|max:255',
                'est_actif'        => 'sometimes|boolean',
            ]);

            $avant = $compteDestination->only(['libelle', 'type_compte', 'numero_compte', 'banque_operateur', 'est_actif']);
            $compteDestination->update($validated);
            AuditLogger::log('PARTENAIRE_COMPTE.UPDATE', $request->user(), 'partenaire_comptes_destination',
                (string) $compteDestination->id, $avant, $validated);

            return $this->sendResponse(
                ['compte_destination' => new PartenaireCompteDestinationResource($compteDestination->fresh())],
                'Compte de destination modifié avec succès.'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Données invalides.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * DELETE /administration/panel-admin/configurations/partenaires-financiers/{partenaireFinancier}/comptes-destination/{compteDestination}
     */
    public function destroy(PartenairesFinancier $partenaireFinancier, PartenaireCompteDestination $compteDestination): JsonResponse
    {
        try {
            $avant = $compteDestination->only(['libelle', 'type_compte', 'numero_compte', 'banque_operateur']);
            $compteDestination->delete();
            AuditLogger::log('PARTENAIRE_COMPTE.DELETE', request()->user(), 'partenaire_comptes_destination',
                (string) $compteDestination->id, $avant, null);

            return $this->sendResponse([], 'Compte de destination supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
