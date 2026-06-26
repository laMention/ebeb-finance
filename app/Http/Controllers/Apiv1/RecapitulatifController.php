<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Services\RecapitulatifService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecapitulatifController extends BaseController
{
    public function __construct(private readonly RecapitulatifService $service) {}

    /**
     * Récapitulatif mensuel des prélèvements et solde disponible.
     *
     * Paramètres optionnels :
     *  - mois  (int 1–12)   + annee (int) : récap d'un mois précis
     *  - date_debut (Y-m-d) + date_fin    : intervalle libre
     *  Sans paramètre : mois courant
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'mois'       => 'nullable|integer|between:1,12',
            'annee'      => 'nullable|integer|min:2000|max:2100',
            'date_debut' => 'nullable|date|before_or_equal:date_fin',
            'date_fin'   => 'nullable|date|after_or_equal:date_debut',
        ]);

        try {
            $recap = $this->service->recapitulatif(
                $request->user(),
                $request->only(['mois', 'annee', 'date_debut', 'date_fin'])
            );

            return $this->sendResponse($recap, 'Récapitulatif récupéré avec succès.');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
