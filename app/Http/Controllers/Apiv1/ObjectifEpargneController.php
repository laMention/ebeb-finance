<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreObjectifEpargneRequest;
use App\Http\Requests\UpdateObjectifEpargneRequest;
use App\Http\Resources\ObjectifEpargneResource;
use App\Models\ObjectifEpargne;
use App\Services\ObjectifEpargneService;
use Illuminate\Http\Request;

class ObjectifEpargneController extends BaseController
{
    public function __construct(private ObjectifEpargneService $objectifEpargneService) {}

    public function index(Request $request)
    {
        $objectif = $this->objectifEpargneService->obtenirObjectifActif($request->user());

        if (!$objectif) {
            return $this->sendError("Aucun objectif d'épargne configuré.", [], 404);
        }

        return $this->sendResponse(new ObjectifEpargneResource($objectif), "Objectif d'épargne récupéré avec succès.");
    }

    public function store(StoreObjectifEpargneRequest $request)
    {
        try {
            $resultat = $this->objectifEpargneService->creerObjectif($request->user(), $request->validated());

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse(
                new ObjectifEpargneResource($resultat['objectif']),
                "Objectif d'épargne créé avec succès."
            );
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function update(UpdateObjectifEpargneRequest $request, ObjectifEpargne $objectifEpargne)
    {
        try {
            if ($objectifEpargne->user_id !== $request->user()->id) {
                return $this->sendError('Accès non autorisé.', [], 403);
            }

            $resultat = $this->objectifEpargneService->mettreAjourObjectif($objectifEpargne, $request->validated());

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse(
                new ObjectifEpargneResource($resultat['objectif']),
                "Objectif d'épargne mis à jour avec succès."
            );
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function destroy(Request $request, ObjectifEpargne $objectifEpargne)
    {
        try {
            if ($objectifEpargne->user_id !== $request->user()->id) {
                return $this->sendError('Accès non autorisé.', [], 403);
            }

            $resultat = $this->objectifEpargneService->supprimerObjectif($objectifEpargne);

            return $this->sendResponse([], $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
