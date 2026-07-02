<?php

namespace App\Http\Controllers\Apiv1\ControlPanel;

use App\Http\Controllers\BaseController;
use App\Http\Requests\ControlPanel\ChangerEtatPlateformeRequest;
use App\Services\PlateformeStateService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlatformStateController extends BaseController
{
    public function __construct(private readonly PlateformeStateService $service)
    {
    }

    public function show()
    {
        return $this->sendResponse($this->service->getStatutEffectif(), 'État de la plateforme.');
    }

    public function update(ChangerEtatPlateformeRequest $request)
    {
        try {
            $admin = $request->user();

            if (!$admin->isSuperAdmin()) {
                return $this->sendError("Accès réservé au Super Administrateur principal.", [], 403);
            }

            $data = $request->validated();

            $etat = $this->service->changerStatut(
                statut:    $data['statut'],
                message:   $data['message'] ?? null,
                motif:     $data['motif'] ?? null,
                dateDebut: isset($data['date_debut']) ? Carbon::parse($data['date_debut']) : null,
                dateFin:   isset($data['date_fin']) ? Carbon::parse($data['date_fin']) : null,
                admin:     $admin,
                ip:        $request->ip(),
            );

            return $this->sendResponse($etat, "État de la plateforme mis à jour.");
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function historique(Request $request)
    {
        return $this->sendResponse(
            $this->service->historique((int) $request->query('per_page', 20)),
            'Historique des changements d\'état.',
        );
    }
}
