<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
// use App\Http\Controllers\Controller;
use App\Http\Requests\AjoutDocumentKYCRequest;
use App\Http\Resources\UserResource;
use App\Services\NotificationService;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Services\DocumentService;


class UserController extends BaseController
{
    //
    protected $userService;
    protected $documentKYCService;
    protected $notificationService;

    // Injection de dépendance du Service
    public function __construct(UserService $userService, DocumentService $documentKYCService, NotificationService $notificationService)   
    {
        $this->userService = $userService;
        $this->documentKYCService = $documentKYCService;
        $this->notificationService = $notificationService;
    }

    public function infosUtilisateurConnecte(Request $request): UserResource
    {
         // Récupération des données via le Service
        $user = $this->userService->obtenirInfosUtilisateur($request->user()->id);

        // Retour via la ressource HTTP
        return new UserResource($user);
 
    }
    public function ajouterDocument(AjoutDocumentKYCRequest $request)
    {
        try {
            $validated = $request->validated();

            // Traitement du recto
            if ($request->hasFile('url_recto')) {
                $validated['url_recto'] = $request->file('url_recto')->store('identifications', 'public');
            } else {
                unset($validated['url_recto']);
            }

            // Traitement du verso
            if ($request->hasFile('url_verso')) {
                $validated['url_verso'] = $request->file('url_verso')->store('identifications', 'public');
            } else {
                unset($validated['url_verso']);
            }

            // Traitement du selfie
            if ($request->hasFile('url_selfie')) {
                $validated['url_selfie'] = $request->file('url_selfie')->store('identifications', 'public');
            } else {
                unset($validated['url_selfie']);
            }

            $resultat = $this->documentKYCService->ajouterDocument($validated);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            $type = mettre_en_majuscule('ajout_document');

            $contenu = $resultat['statut_compte'] === 'ACTIF'
                ? [
                    'titre'           => 'Documents ajoutés !',
                    'message'         => "Vos documents ont été ajoutés avec succès.",
                    'sujet'           => 'Ajout de vos documents KYC - ' . config('app.name'),
                    'date_activation' => now()->format('d/m/Y H:i'),
                    'type'            => $type
                ]
                : [
                    'titre'           => 'Documents ajoutés !',
                    'message'         => "Vos documents ont été ajoutés. Votre compte sera activé une fois tous les champs renseignés.",
                    'sujet'           => 'Ajout de vos documents KYC - ' . config('app.name'),
                    'date_activation' => now()->format('d/m/Y H:i'),
                    'type'            => $type
                ];

            $this->notificationService->envoyerNotification(
                $resultat['user_id'],
                'in_app',
                $type,
                $contenu,
                true
            );

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
