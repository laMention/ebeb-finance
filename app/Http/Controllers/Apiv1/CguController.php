<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Services\CguService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Endpoints publics (hors auth:sanctum) : la modale CGU s'affiche avant la
 * création du compte, il n'y a donc pas encore de token disponible.
 */
class CguController extends BaseController
{
    public function __construct(protected CguService $cguService)
    {
    }

    /**
     * Version des CGU actuellement active — affichée dans la modale
     * d'acceptation avant l'inscription.
     */
    public function versionActive(Request $request)
    {
        $version = $this->cguService->versionActive();

        if (!$version) {
            return $this->sendError("Aucune version des CGU n'est actuellement publiée.", [], 404);
        }

        return $this->sendResponse([
            'id'             => $version->id,
            'numero_version' => $version->numero_version,
            'titre'          => $version->titre,
            'contenu'        => $version->contenu,
            'publie_le'      => $version->publie_le,
        ], 'Version des CGU récupérée avec succès.');
    }

    /**
     * Enregistre l'acceptation d'une version des CGU, avant la création du
     * compte (`user_id` reste NULL — rattaché ensuite lors de l'inscription,
     * voir `InscriptionService::inscrire()`).
     */
    public function accepter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cgu_version_id' => ['required', 'uuid', 'exists:cgu_versions,id'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Version des CGU invalide.', $validator->errors(), 422);
        }

        $acceptation = $this->cguService->enregistrerAcceptation(
            $request->input('cgu_version_id'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->sendResponse(['id' => $acceptation->id], 'Acceptation des CGU enregistrée.');
    }
}
