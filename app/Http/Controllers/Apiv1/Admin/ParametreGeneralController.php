<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\SaveParametreGeneralRequest;
use App\Models\ParametreGeneral;
use App\Services\AuditLogger;
use App\Services\ParametreGeneralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParametreGeneralController extends BaseController
{
    public function __construct(private ParametreGeneralService $service) {}

    /** GET /parametre-general — retourne les paramètres généraux */
    public function show(): JsonResponse
    {
        $resultat = $this->service->obtenir();
        return $this->sendResponse($resultat, 'Paramètres généraux récupérés.');
    }

    /**
     * POST /parametre-general — sauvegarde groupée (multipart/form-data)
     * Accepte à la fois les champs texte et les fichiers (logos, images SEO).
     */
    public function save(SaveParametreGeneralRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Séparer les fichiers des champs texte
        $champsTexte = array_filter($validated, fn($v) => !($v instanceof \Illuminate\Http\UploadedFile));
        $fichiers    = array_filter($request->allFiles(), fn($k) => in_array($k, ParametreGeneral::$CHAMPS_FICHIERS, true), ARRAY_FILTER_USE_KEY);

        $avant    = ParametreGeneralService::getInstance()->only(['nom_plateforme', 'email_contact', 'site_web']);
        $resultat = $this->service->sauvegarder($champsTexte, $fichiers, $request->user()?->id);

        AuditLogger::log(
            'PARAMETRE_GENERAL.UPDATE',
            $request->user(),
            'parametre_generals',
            '1',
            $avant,
            array_keys(array_merge($champsTexte, $fichiers))
        );

        return $this->sendResponse($resultat, 'Paramètres généraux enregistrés avec succès.');
    }

    /**
     * DELETE /parametre-general/fichier/{champ} — supprime un logo/image
     */
    public function supprimerFichier(Request $request, string $champ): JsonResponse
    {
        $resultat = $this->service->supprimerFichier($champ, $request->user()?->id);

        if (!$resultat['success']) {
            return $this->sendError($resultat['message'], [], 422);
        }

        AuditLogger::log('PARAMETRE_GENERAL.DELETE_FILE', $request->user(), 'parametre_generals', '1', ['champ' => $champ]);

        return $this->sendResponse($resultat, $resultat['message']);
    }
}
