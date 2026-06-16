<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\RecevoirPaiementRequest;
use App\Http\Resources\PaiementEntrantResource;
use App\Models\ParametreGlobal;
use App\Services\PaiementService;
use Illuminate\Http\Request;

class PaiementEntrantController extends BaseController
{
    public function __construct(private PaiementService $paiementService) {}

    /**
     * Webhook : reçoit une notification de paiement depuis un opérateur Mobile Money.
     * Endpoint public — validé par un secret partagé dans le header X-Webhook-Secret.
     */
    public function webhook(RecevoirPaiementRequest $request)
    {
        // return $request->validated();
        // Validation du secret webhook
        $secretAttendu = ParametreGlobal::where('cle', 'WEBHOOK_SECRET')->value('valeur');
        if ($secretAttendu && $request->header('X-Webhook-Secret') !== $secretAttendu) {
            return $this->sendError('Signature webhook invalide.', [], 401);
        }

        try {
            $resultat = $this->paiementService->traiterPaiement($request->validated());

            return $this->sendResponse(
                new PaiementEntrantResource($resultat['paiement']),
                'Paiement traité avec succès.'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Compte ou QR Code introuvable pour ce paiement.', [], 404);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Liste les paiements reçus par l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        try {
            $paiements = $this->paiementService->listerPaiements(
                $request->user(),
                (int) $request->get('per_page', 15)
            );

            return $this->sendResponse(
                PaiementEntrantResource::collection($paiements)->response()->getData(true),
                'Paiements récupérés avec succès.'
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Détail d'un paiement avec la répartition complète des fonds.
     */
    public function show(Request $request, string $paiementId)
    {
        try {
            $paiement = \App\Models\PaiementEntrant::where('user_id', $request->user()->id)
                ->with(['operation.sous_operations.type_cotisation', 'operation.sous_operations.objectif_epargne', 'compte_mobile_money'])
                ->findOrFail($paiementId);

            return $this->sendResponse(
                new PaiementEntrantResource($paiement),
                'Paiement récupéré avec succès.'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->sendError('Paiement introuvable.', [], 404);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
