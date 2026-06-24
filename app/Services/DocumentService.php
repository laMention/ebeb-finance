<?php
namespace App\Services;

use App\Models\DocumentKYC;
use App\Models\User;
use Carbon\Carbon;
use DB;


class DocumentService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function recupererInfoDocumentUtilisateur(string $userId): array
    {
        try {
            // Récupérer tous les documents de l'utilisateur
            $documents = DocumentKYC::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            if ($documents->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Aucun document trouvé pour cet utilisateur',
                    'documents' => []
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Documents récupérés avec succès',
                'documents' => $documents,
                'count' => $documents->count()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération des documents: ' . $e->getMessage(),
                'documents' => []
            ];
        }
    }

    /**
     * Récupérer un document spécifique par son ID
     * 
     * @param string $documentId
     * @return array
     */
    public function recupererDocumentParId(string $documentId) 
    {
        try {
            $document = DocumentKYC::find($documentId);
            
            if (!$document) {
                return [
                    'success' => false,
                    'message' => 'Document non trouvé',
                    'document' => null
                ];
            }
            
            return $document;
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'document' => null
            ];
        }
    }

    /**
     * Récupérer le dernier document d'un utilisateur par type
     * 
     * @param string $userId
     * @param string $typeDocument
     * @return array
     */
    public function recupererDernierDocumentParType(string $userId, string $typeDocument): array
    {
        try {
            $document = DocumentKYC::where('user_id', $userId)
                ->where('type_document', $typeDocument)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$document) {
                return [
                    'success' => false,
                    'message' => "Aucun document de type '$typeDocument' trouvé",
                    'document' => null
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Document récupéré avec succès',
                'document' => $document
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'document' => null
            ];
        }
    }
    /**
     * Verifier un document
     * Valider un document active automatiquement le compte
     */
    
    /**
     * Valider ou rejeter un document KYC
     * 
     * @param DocumentKYC $documentKYC
     * @return array
     */
    public function validerRejeter(DocumentKYC $documentKYC, array $data): array
    {
        try {
            DB::beginTransaction();

            // Validation du document
            if ($data['action'] === "VALIDER") {
                
                // Vérifier si tous les documents sont complets
                if (empty($documentKYC->user_id) || 
                    empty($documentKYC->type_document) || 
                    empty($documentKYC->numero_document) || 
                    empty($documentKYC->url_recto) ||
                    empty($documentKYC->url_verso) || 
                    empty($documentKYC->url_selfie)) {
                    
                    return [
                        "success" => false,
                        "message" => "Impossible de valider car les documents demandés ne sont pas complets",
                    ];
                }

                // Mettre à jour le statut du document
                $documentKYC->update([
                    "statut" => mettre_en_majuscule("VALIDE"),
                    "valide_par" => mettre_en_majuscule(auth()->user()->nom.' '.auth()->user()->prenom),
                    "validated_at" => Carbon::now()
                ]);

                // Récupérer l'utilisateur
                $user = User::find($documentKYC->user_id);
                
                if (!$user) {
                    DB::rollBack();
                    return [
                        "success" => false,
                        "message" => "Utilisateur non trouvé"
                    ];
                }

                // Activer le compte utilisateur
                $user->update([
                    "statut" => mettre_en_majuscule("ACTIF"),
                    "date_activation" => $this->mettreAjourDateActivationCompteUser($user)
                ]);

                // Envoyer une notification d'activation de compte
                $notificationResult = $this->notificationService->notifierActivationCompte($user, [
                    'canal' => 'email', // ou 'in-app', 'sms', 'push'
                    'message_personnalise' => 'Votre compte a été activé suite à la validation de vos documents KYC.'
                ]);

                DB::commit();

                // Log de l'action
                \Log::info('Document validé et compte activé', [
                    'document_id' => $documentKYC->id,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'notification_envoyee' => $notificationResult['success']
                ]);

                return [
                    "success" => true,
                    "message" => "Document validé et compte activé avec succès",
                    "document" => $documentKYC,
                    "user" => $user,
                    "notification" => $notificationResult
                ];
                
            } 
            elseif ($data['action'] === "REJETER") {
                
                // Récupérer l'utilisateur
                $user = User::find($documentKYC->user_id);
                
                if (!$user) {
                    DB::rollBack();
                    return [
                        "success" => false,
                        "message" => "Utilisateur non trouvé"
                    ];
                }

                if(isset($data["raison"])) {
                    $raison = $data["raison"];
                } else {
                    $raison = "Les documents fournis ne sont pas conformes à nos exigences. Veuillez vérifier que tous les documents sont lisibles et authentiques.";
                }

                // Mettre à jour le statut du document
                $documentKYC->update([
                    "statut" => mettre_en_majuscule("REJETE"),
                    "motif_rejet" => $raison,
                    "valide_par" => mettre_en_majuscule(auth()->user()->nom.' '.auth()->user()->prenom),
                    "validated_at" => Carbon::now()
                ]);

                // Préparer la raison du rejet (à personnaliser selon vos besoins)
                
                // Envoyer une notification de rejet
                $notificationResult = $this->notificationService->notifierRejetDocument(
                    $user,
                    $documentKYC->type_document,
                    $raison,
                    ['canal' => 'email']
                );

                DB::commit();

                // Log de l'action
                \Log::info('Document rejeté', [
                    'document_id' => $documentKYC->id,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'notification_envoyee' => $notificationResult['success']
                ]);

                return [
                    "success" => true,
                    "message" => "Document rejeté avec succès",
                    "document" => $documentKYC,
                    "user" => $user,
                    "notification" => $notificationResult
                ];
            }
            else {
                DB::rollBack();
                return [
                    "success" => false,
                    "message" => "Action non reconnue. Les actions valides sont 'valider' ou 'rejeter'"
                ];
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erreur lors de la validation/rejet du document', [
                'document_id' => $documentKYC->id ?? null,
                'action' => $data['action'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                "success" => false,
                "message" => "Une erreur est survenue lors du traitement: " . $e->getMessage()
            ];
        }
    }

    // Mettre à jour les documents
    public function modifierDocument(DocumentKYC $documentKYC, array $data)
    {
        try {
            if (!$documentKYC) {
                return ['success' => false, 'message' => 'Document non trouvé'];
            }

            $champsAMettreAJour = [];

            if (array_key_exists('type_document', $data))
                $champsAMettreAJour['type_document'] = mettre_en_majuscule($data['type_document']);
            if (array_key_exists('numero_document', $data))
                $champsAMettreAJour['numero_document'] = mettre_en_majuscule($data['numero_document']);
            if (array_key_exists('document_etablie_le', $data))
                $champsAMettreAJour['document_etablie_le'] = $data['document_etablie_le'];
            if (array_key_exists('document_expire_le', $data))
                $champsAMettreAJour['document_expire_le'] = $data['document_expire_le'];
            if (array_key_exists('url_recto', $data))
                $champsAMettreAJour['url_recto'] = $data['url_recto']; // chemin relatif uniquement
            if (array_key_exists('url_verso', $data))
                $champsAMettreAJour['url_verso'] = $data['url_verso'];
            if (array_key_exists('url_selfie', $data))
                $champsAMettreAJour['url_selfie'] = $data['url_selfie'];

            if (empty($champsAMettreAJour)) {
                return ['success' => false, 'message' => 'Aucune donnée valide à mettre à jour'];
            }

            if (isset($champsAMettreAJour['document_etablie_le'], $champsAMettreAJour['document_expire_le'])) {
                if (!verifier_validite($champsAMettreAJour['document_etablie_le'], $champsAMettreAJour['document_expire_le'])) {
                    return ['success' => false, 'message' => 'Le document est expiré ou invalide'];
                }
            }

            // Mise à jour partielle — on ne touche que les champs fournis
            $documentKYC->update($champsAMettreAJour);
            $documentKYC->refresh();

            // Vérifier si tous les champs requis sont remplis
            $champsRequis = ['type_document', 'numero_document', 'document_etablie_le', 'document_expire_le', 'url_recto', 'url_verso'];
            $tousChampsRemplis = true;

            foreach ($champsRequis as $champ) {
                if (is_null($documentKYC->$champ) || $documentKYC->$champ === '') {
                    $tousChampsRemplis = false;
                    break;
                }
            }

            return [
                'success'  => true,
                'message'  => $tousChampsRemplis
                    ? 'Document mis à jour avec succès. Il est en attente de validation par un administrateur.'
                    : 'Document mis à jour avec succès.',
                'document' => $documentKYC->fresh(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du document', [
                'document_id' => $documentKYC->id ?? null,
                'error'       => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
        }
    }

     /**
     * Mettre à jour uniquement le statut d'un document
     * 
     * @param DocumentKYC $documentKYC
     * @param string $statut
     * 
     * @return array
     */
    public function modifierStatutDocument(DocumentKYC $documentKYC, string $statut): array
    {
        try {
            $statutValide = mettre_en_majuscule($statut);
            
            // Vérifier si le statut est valide
            $statutsAutorises = ['EN_ATTENTE', 'VALIDE', 'REJETE' ];
            if (!in_array($statutValide, $statutsAutorises)) {
                return [
                    'success' => false,
                    'message' => 'Statut invalide. Statuts autorisés: ' . implode(', ', $statutsAutorises)
                ];
            }

            $data = ['statut' => $statutValide];
            
            // Ajouter un commentaire si fourni
            // if ($commentaire) {
            //     $data['commentaire_validation'] = $commentaire;
            // }

            $documentKYC->update($data);

            return [
                'success' => true,
                'message' => 'Statut du document mis à jour avec succès',
                'document' => $documentKYC,
                'ancien_statut' => $documentKYC->getOriginal('statut'),
                'nouveau_statut' => $statutValide
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    // Suprimer un document
    public function supprimerDocument(DocumentKYC $documentKYC){
        try {
            if($documentKYC->delete()){
                return [
                    'success'=> true,
                    'message'=> 'Document supprimé avec succès'
                    ];
            }else{
                return [
                    'success'=> false,
                    'message'=> 'Impossible de supprimer'
                    ];
            }


        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    // Ajouter un document KYC
    public function ajouterDocument(array $data)
    {
        try {
            // Vérifier la validité des dates si fournies
            if (isset($data['document_etablie_le'], $data['document_expire_le'])) {
                if (!verifier_validite($data['document_etablie_le'], $data['document_expire_le'])) {
                    return [
                        'success' => false,
                        'message' => 'Le document est expiré ou invalide'
                    ];
                }
            }

            $documentKYC = DocumentKYC::create([
                'user_id'             => $data['user_id'],
                'type_document'       => mettre_en_majuscule($data['type_document']),
                'numero_document'     => mettre_en_majuscule($data['numero_document']),
                'document_etablie_le' => $data['document_etablie_le'],
                'document_expire_le'  => $data['document_expire_le'],
                'url_recto'           => $data['url_recto'] ?? null,
                'url_verso'           => $data['url_verso'] ?? null,
                'url_selfie'          => $data['url_selfie'] ?? null,
                'statut'              => 'EN_ATTENTE',
            ]);

            // Vérifier si tous les champs requis sont remplis
            $champsRequis = ['type_document', 'numero_document', 'document_etablie_le', 'document_expire_le', 'url_recto', 'url_verso'];
            $tousChampsRemplis = true;

            foreach ($champsRequis as $champ) {
                if (is_null($documentKYC->$champ) || $documentKYC->$champ === '') {
                    $tousChampsRemplis = false;
                    break;
                }
            }

            return [
                'success'  => true,
                'message'  => $tousChampsRemplis
                    ? 'Document ajouté avec succès. Il est en attente de validation par un administrateur.'
                    : 'Document ajouté avec succès.',
                'document' => $documentKYC->fresh(),
                'user_id'  => $documentKYC->user_id,
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'ajout du document', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'ajout: ' . $e->getMessage()
            ];
        }
    }

    private function mettreAjourDateActivationCompteUser($user){
        $dateActivation = $user->statut === 'EN_ATTENTE' ? Carbon::now() : $user->date_activation;
        return $dateActivation;
    }
}