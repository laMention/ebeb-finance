<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\PartenaireFinancierResource;
use App\Models\PartenairesFinancier;
use App\Services\AuditLogger;
use App\Services\PartenaireFinancierService;
use App\Services\PartenaireTransmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartenaireFinancierController extends BaseController
{
    protected PartenaireFinancierService $service;

    public function __construct(
        PartenaireFinancierService $service,
        private readonly PartenaireTransmissionService $transmissionService,
    ) {
        $this->service = $service;
    }

    /**
     * Règles de validation communes à la configuration API d'un partenaire.
     */
    private function reglesConfiguration(): array
    {
        return [
            'est_actif'                => 'sometimes|boolean',
            'url_api_reversement'      => 'sometimes|nullable|url|max:255',
            'url_api_consultation'     => 'sometimes|nullable|url|max:255',
            'url_webhook'              => 'sometimes|nullable|url|max:255',
            'methode_authentification' => 'sometimes|nullable|in:API_KEY,OAUTH,BASIC_AUTH',
            'identifiants_api'         => 'sometimes|nullable|array',
            'format_echange'           => 'sometimes|in:JSON,XML',
        ];
    }

    /**
     * Redacte les identifiants sensibles avant écriture dans le journal d'audit.
     */
    private function pourAudit(array $data): array
    {
        if (array_key_exists('identifiants_api', $data)) {
            $data['identifiants_api'] = '••• (chiffré, non journalisé)';
        }

        return $data;
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
            $validated = $request->validate(array_merge([
                'nom'  => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:partenaires_financiers,code',
                'type' => 'required|string|max:100',
            ], $this->reglesConfiguration()));

            $partenaire = $this->service->creer($validated);
            AuditLogger::log('PARTENAIRE.CREATE', $request->user(), 'partenaires_financiers',
                (string) $partenaire->id, null, $this->pourAudit($validated));

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
            $partenaireFinancier->loadCount('reversements')->load('comptesDestination');

            return $this->sendResponse(
                ['partenaire' => new PartenaireFinancierResource($partenaireFinancier)],
                'Partenaire financier récupéré avec succès.'
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * GET /administration/panel-admin/configurations/partenaires-financiers/{partenaireFinancier}/tester-configuration
     * Vérifie que la configuration est complète avant de pouvoir valider un reversement.
     */
    public function testerConfiguration(PartenairesFinancier $partenaireFinancier): JsonResponse
    {
        try {
            $resultat = $this->transmissionService->verifierConfiguration($partenaireFinancier);

            return $this->sendResponse($resultat, $resultat['ok']
                ? 'Configuration complète.'
                : 'Configuration incomplète.');

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
            $validated = $request->validate(array_merge([
                'nom'  => 'sometimes|required|string|max:255',
                'code' => 'sometimes|required|string|max:50|unique:partenaires_financiers,code,' . $partenaireFinancier->id,
                'type' => 'sometimes|required|string|max:100',
            ], $this->reglesConfiguration()));

            $avant      = $partenaireFinancier->only(['nom', 'code', 'type', 'est_actif', 'methode_authentification']);
            $partenaire = $this->service->modifier($partenaireFinancier, $validated);
            AuditLogger::log('PARTENAIRE.UPDATE', $request->user(), 'partenaires_financiers',
                (string) $partenaireFinancier->id, $avant, $this->pourAudit($validated));

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
            $avant = $partenaireFinancier->only(['nom', 'code', 'type']);
            $this->service->supprimer($partenaireFinancier);
            AuditLogger::log('PARTENAIRE.DELETE', request()->user(), 'partenaires_financiers',
                (string) $partenaireFinancier->id, $avant, null);

            return $this->sendResponse([], 'Partenaire financier supprimé avec succès.');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
