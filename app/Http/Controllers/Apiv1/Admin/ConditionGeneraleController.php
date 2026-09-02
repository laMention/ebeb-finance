<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConditionGeneraleRequest;
use App\Http\Requests\UpdateConditionGeneraleRequest;
use App\Models\ConditionGenerale;
use App\Services\AdminConditionGeneraleService;
use Illuminate\Http\Request;

class ConditionGeneraleController extends BaseController
{
    private $conditionGeneraleService;

    public function __construct(AdminConditionGeneraleService $conditionGeneraleService)
    {
        $this->conditionGeneraleService = $conditionGeneraleService;
    }

    public function index(Request $request)
    {
        try {
            $resultat = $this->conditionGeneraleService->afficherToutesLesConditionGenerales($request->only([
                'search', 'est_active', 'per_page', 'page',
            ]));

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function store(StoreConditionGeneraleRequest $request)
    {
        try {
            $validated = $request->validated();

            $validated['slug'] = \Str::slug($validated['titre']);
            $validated['est_active'] = true;

            $resultat = $this->conditionGeneraleService->enregistrerConditionGenerale($validated);

            if (isset($resultat['success']) && !$resultat['success']) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, 'Condition générale enregistrée avec succès.');
        } catch (\Exception $e) {
            
            return $this->throw($e);
        }
    }

    public function show(ConditionGenerale $conditionGenerale)
    {

        try {
            return $this->sendResponse(
                ['success' => true, 'data' => $this->conditionGeneraleService->afficherConditionGenerale($conditionGenerale)],
                'Condition générale récupérée avec succès'
            );
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    
    public function update(UpdateConditionGeneraleRequest $request, ConditionGenerale $conditionGenerale)
    {
        try {
            $validated = $request->validated();
            // $conditionGenerale = ConditionGenerale::findOrFail($id);

            $slug = \Str::slug($validated['titre']);
            $active = $validated['est_active'] ?? true;

            $resultat = $this->conditionGeneraleService->mettreAJourConditionGenerale($conditionGenerale, [
                'titre' => $validated['titre'],
                'description' => $validated['description'],
                'est_active' => $active,
                'slug' => $slug,
            ]);

            if (isset($resultat['success']) && !$resultat['success']) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, 'Condition générale mise à jour avec succès.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function destroy(ConditionGenerale $conditionGenerale)
    {
        try {
            // $conditionGenerale = ConditionGenerale::findOrFail($id);

            $resultat = $this->conditionGeneraleService->supprimerConditionGenerale($conditionGenerale);

            if (isset($resultat['success']) && !$resultat['success']) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, 'Condition générale supprimée avec succès.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
