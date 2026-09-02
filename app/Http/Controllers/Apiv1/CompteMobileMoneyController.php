<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreCompteMobileMoneyRequest;
use App\Http\Resources\CompteMobileMoneyResource;
use App\Models\CompteMobileMoney;
use App\Services\CompteMobileMoneyService;
use App\Services\MoyenPaiementService;
use Illuminate\Http\JsonResponse;

class CompteMobileMoneyController extends BaseController
{
    protected CompteMobileMoneyService $compteMobileMoneyService;

    public function __construct(CompteMobileMoneyService $compteMobileMoneyService)
    {
        $this->compteMobileMoneyService = $compteMobileMoneyService;
    }

    /**
     * Liste des moyens de paiement actifs (Wave, Orange Money, ...), pour que
     * l'utilisateur puisse choisir celui qui recevra ses paiements avant de
     * rattacher un compte mobile money. Réutilise le service déjà utilisé par
     * le panel admin, en ne renvoyant que les champs utiles côté mobile.
     */
    public function moyensPaiement(MoyenPaiementService $moyenPaiementService): JsonResponse
    {
        try {
            $resultat = $moyenPaiementService->listerMoyensPaiement(['est_actif' => true]);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            $moyens = collect($resultat['data'])->map(fn ($moyen) => [
                'id'      => $moyen->id,
                'libelle' => $moyen->libelle,
                'code'    => $moyen->code,
                'operateur' => $moyen->operateur,
                'logo_url' => $moyen->logo ? storage_public_path($moyen->logo) : null,
                'par_defaut' => (bool) $moyen->par_defaut,
                // Couleur propre au moyen de paiement, gérée en base
                // (`moyen_paiements.couleur`) — jamais déduite du code
                // opérateur, pour rester éditable par l'admin sans déploiement.
                'couleur' => $moyen->couleur,
            ])->values();

            return $this->sendResponse($moyens, 'Moyens de paiement récupérés avec succès');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $user = auth()->user();
            $resultat = $this->compteMobileMoneyService->listerComptesUtilisateur($user);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            return $this->sendResponse(
                CompteMobileMoneyResource::collection($resultat['data'])->response()->getData(true),
                $resultat['message']
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function store(StoreCompteMobileMoneyRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $resultat = $this->compteMobileMoneyService->creerCompte($user, $request->validated());

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse(
                new CompteMobileMoneyResource($resultat['data']),
                $resultat['message']
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function definirPrincipal(CompteMobileMoney $compteMobileMoney): JsonResponse
    {
        try {
            $user = auth()->user();
            $resultat = $this->compteMobileMoneyService->definirComptePrincipal($user, $compteMobileMoney);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse(
                new CompteMobileMoneyResource($resultat['data']),
                $resultat['message']
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
