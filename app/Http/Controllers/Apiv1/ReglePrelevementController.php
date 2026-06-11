<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\ConfigurerReglesRequest;
use App\Http\Requests\StoreReglePrelevementRequest;
use App\Models\ReglePrelevement;
use App\Services\ReglePrelevementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReglePrelevementController extends BaseController
{
    protected ReglePrelevementService $reglePrelevementService;

    public function __construct(ReglePrelevementService $reglePrelevementService)
    {
        $this->reglePrelevementService = $reglePrelevementService;
    }

    /**
     * Lister les règles de prélèvement de l'utilisateur.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $resultat = $this->reglePrelevementService->listerReglesUtilisateur($userId);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Afficher une règle spécifique.
     */
    public function show(ReglePrelevement $reglePrelevement, Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            // Vérifier que la règle appartient à l'utilisateur
            if ($reglePrelevement->user_id !== $userId) {
                return $this->sendError('Accès non autorisé', [], 403);
            }

            $resultat = $this->reglePrelevementService->obtenirRegle($reglePrelevement->id, $userId);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 404);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Récupérer les types de cotisations avec les règles configurées.
     * Utilisé par l'écran mobile pour afficher les types et pré-remplir les champs.
     */
    public function types(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $resultat = $this->reglePrelevementService->obtenirTypeCotisationsAvecRegles($userId);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Créer ou mettre à jour une règle de prélèvement.
     * Si une règle existe pour ce type de cotisation, elle est modifiée.
     * Sinon, une nouvelle est créée.
     */
    public function configurerRegleTypeCotisation(StoreReglePrelevementRequest $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $resultat = $this->reglePrelevementService->sauvegarderRegle($userId, $request->validated());

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Supprimer une règle de prélèvement.
     */
    public function destroy(ReglePrelevement $reglePrelevement, Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            // Vérifier que la règle appartient à l'utilisateur
            if ($reglePrelevement->user_id !== $userId) {
                return $this->sendError('Accès non autorisé', [], 403);
            }

            $resultat = $this->reglePrelevementService->supprimerRegle($reglePrelevement);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse([], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Basculer le statut (actif/inactif) d'une règle.
     */
    public function basculerStatut(ReglePrelevement $reglePrelevement, Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            // Vérifier que la règle appartient à l'utilisateur
            if ($reglePrelevement->user_id !== $userId) {
                return $this->sendError('Accès non autorisé', [], 403);
            }

            $resultat = $this->reglePrelevementService->basculerStatut($reglePrelevement);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Valider et sauvegarder plusieurs règles en une seule requête.
     * Point d'entrée principal pour la configuration des prélèvements depuis l'écran mobile.
     */
    public function configurer(ConfigurerReglesRequest $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $resultat = $this->reglePrelevementService->validerEtSauvegarderRegles(
                $userId,
                $request->validated('regles')
            );

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Réorganiser les règles par ordre de priorité.
     */
    public function reordonner(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $validated = $request->validate([
                'ordres'              => ['required', 'array'],
                'ordres.*.regle_id'   => ['required', 'uuid', 'exists:regle_prelevements,id'],
                'ordres.*.ordre_priorite' => ['required', 'integer', 'min:1'],
            ]);

            $resultat = $this->reglePrelevementService->reordonnerRegles($userId, $validated['ordres']);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
