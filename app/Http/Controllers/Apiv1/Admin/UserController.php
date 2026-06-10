<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
// use App\Http\Controllers\Controller;
use App\Http\Requests\AjoutDocumentKYCRequest;
use App\Http\Requests\ModifierDocumentKYCRequest;
use App\Models\DocumentKYC;
use App\Services\DocumentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends BaseController
{
    //
    protected $documentKYCService;
    protected $notificationService;

    public function __construct(DocumentService $documentKYCService, NotificationService $notificationService)
    {
        $this->documentKYCService = $documentKYCService;
        $this->notificationService = $notificationService;
    }

    // Valider ou rejeter les documents d'un utilisateur
    public function verificationDocument(Request $request){
        try {
            $data = $request->all();
            //recuperation des infos du doc
            $document = $this->documentKYCService->recupererDocumentParId($data['document_id']);

            if(!$document["success"] === false ){
                return $this->sendError($document['message'],[],400);
            }

            $resultat = $this->documentKYCService->validerRejeter($document, $data);

            if(!$resultat["success"] === false ){
                return $this->sendError($resultat['message'],[],400);
            }

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            //throw $th;
            return $this->throw($e);
        }
    }

    // Mettre à jour les documents KYC
    public function mettreAjourDocument(DocumentKYC $documentKYC, ModifierDocumentKYCRequest $request)
    {
        try {
            $validated = $request->validated();

            // Traitement du recto
            if ($request->hasFile('url_recto')) {
                if ($documentKYC->url_recto) {
                    Storage::disk('public')->delete($documentKYC->url_recto);
                }
                // store() retourne le chemin relatif : "identifications/xxx.png"
                $validated['url_recto'] = $request->file('url_recto')->store('identifications', 'public');
            } else {
                // Ne pas envoyer la clé au service si aucun fichier fourni
                unset($validated['url_recto']);
            }

            // Traitement du verso
            if ($request->hasFile('url_verso')) {
                if ($documentKYC->url_verso) {
                    Storage::disk('public')->delete($documentKYC->url_verso);
                }
                $validated['url_verso'] = $request->file('url_verso')->store('identifications', 'public');
            } else {
                unset($validated['url_verso']);
            }

            // Traitement du selfie
            if ($request->hasFile('url_selfie')) {
                if ($documentKYC->url_selfie) {
                    Storage::disk('public')->delete($documentKYC->url_selfie);
                }
                $validated['url_selfie'] = $request->file('url_selfie')->store('identifications', 'public');
            } else {
                unset($validated['url_selfie']);
            }

            $resultat = $this->documentKYCService->modifierDocument($documentKYC, $validated);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }

            $type = mettre_en_majuscule('mise_à_jour_document');

            if ($resultat['statut_compte'] === 'ACTIF') {
                $contenu = [
                    'titre'           => 'Documents mis à jour !',
                    'message'         => "Vos documents ont été mis à jour avec succès. Votre compte est désormais activé.",
                    'sujet'           => 'Mise à jour de vos documents KYC - ' . config('app.name'),
                    'date_activation' => now()->format('d/m/Y H:i'),
                    'type'            => $type
                ];
            } else {
                $contenu = [
                    'titre'           => 'Documents mis à jour !',
                    'message'         => "Vos documents ont été mis à jour avec succès. Votre compte sera activé une fois tous les champs renseignés.",
                    'sujet'           => 'Mise à jour de vos documents KYC - ' . config('app.name'),
                    'date_activation' => now()->format('d/m/Y H:i'),
                    'type'            => $type
                ];
            }

            $this->notificationService->envoyerNotification(
                $documentKYC->user_id,
                'in-app',
                $type,
                $contenu,
                true
            );

            return $this->sendResponse($resultat, $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    // Ajouter un document KYC
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
                    'message'         => "Vos documents ont été ajoutés avec succès. Votre compte est désormais activé.",
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

    // Supprimer un document
    public function supprimerDocument(DocumentKYC $documentKYC){
        try {
            $resultat = $this->documentKYCService->ajouterDocument($documentKYC);

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 400);
            }
            return $this->sendResponse([], $resultat['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
