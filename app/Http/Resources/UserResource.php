<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "uuid" => $this->id,
            "nom" => $this->nom,
            "prenom" => $this->nom,
            "email" => $this->email,
            "date_naissance" => format_date_fr_chiffre($this->date_naissance) ?? null,
            "lieu_naissance" => $this->lieu_naissance,
            "telephone" => $this->telephone,
            "profession" => $this->profession,
            "numero_cnps" => $this->numero_cnps,
            "numero_cmu" => $this->numero_cmu,
            "statut" => $this->statut,
            "type_carte" => $this->type_carte,
            "pays" => $this->pays,
            "ville" => $this->ville,
            "quartier" => $this->quartier,
            "village" => $this->village,
            "adresse_postale" => $this->adresse_postale,
            "sexe" => $this->sexe,
            "situation_familiale" => $this->situation_familiale,
            "nombre_enfants" => $this->nombre_enfants,
            "date_activation" => $this->date_activation,
            "informationProfessionnelle" => $this->whenLoaded('informationProfessionnelle', function () {
                return new InformationProfessionnelleResource($this->informationProfessionnelle);
            }),
            "documentKYCs" => $this->whenLoaded('documentKYCs', function () {
                return $this->documentKYCs->map(function ($document) {
                    return [
                        'uuid' => $document->id,
                        'type_document' => $document->type_document,
                        'numero_document' => $document->numero_document,
                        'document_etablie_le' => format_date_fr_lettre($document->document_etablie_le),
                        'document_expire_le' => format_date_fr_lettre($document->document_expire_le),
                        'document_recto' => $document->url_recto ? storage_public_path($document->url_recto) : null,
                        'document_verso' => $document->url_verso ? storage_public_path($document->url_verso) : null,
                        'photo_selfie' =>  $document->url_selfie ? storage_public_path($document->url_selfie): null,
                        'statut' => $document->statut,
                        'motif_rejet' => $document->motif_rejet ?? null,
                        'validated_at' => $document->validated_at ? format_date_fr_chiffre($document->validated_at) : null,
                    ];
                });
            }),           
            
            // $this->whenLoaded('documentKYCs', function () {
            //     return new DocumentKYCResource($this->documentKYCs);
            // }),
            "declarationRevenu" => $this->whenLoaded('declarationRevenu', function () {
                return new DeclarationRevenuResource($this->declarationRevenu);
            }),
            'compteMobileMoneys' => $this->whenLoaded('compteMobileMoneys', function () {
                return $this->compteMobileMoneys->map(function ($mobile_money) {
                    return [
                        'uuid' => $this->mobile_money->id,
                        'operateur' => $this->mobile_money->operateur,
                        'numero_compte' => $this->mobile_money->numero_compte,
                        'est_principal' => $this->mobile_money->est_principal,
                        'est_actif' => $this->mobile_money->est_actif,
                    ];
                });
            }),
            "enfants" => $this->whenLoaded("enfants", function () {
                return $this->enfants->map(function ($enfant) {
                    return [
                        "nom" => $this->enfant->nom,
                        "prenom" => $this->enfant->prenom,
                        "date_naissance" => $this->enfant->date_naissance,
                        "lieu_naissance" => $this->enfant->lieu_naissance,
                    ];
                });
            }),
            "cotisations" =>$this->whenLoaded("cotisations", function () {
                return $this->cotisations->map(function ($cotisation) {
                    return [
                        "mois" => $this->cotisation->mois,
                        "annee" => $this->cotisation->annee,
                        "montant_verse" => $this->cotisation->montant_verse,
                        "montant_objectif" => $this->cotisation->montant_objectif,
                        "statut" => $this->cotisation->statut,
                        "numero_adherent" => $this->cotisation->numero_adherent,
                        "date_paiement" => $this->cotisation->date_paiement,
                        "typeCotisation" => $this->whenLoaded('typeCotisation', function () {
                            return [
                                'uuid' => $this->typeCotisation->id,
                                'libelle' => $this->typeCotisation->libelle,
                                'code' => $this->typeCotisation->code,
                                'categorie' => $this->typeCotisation->categorie,
                                'est_obligatoire' => $this->typeCotisation->est_obligatoire,
                                'est_actif' => $this->typeCotisation->est_actif,
                            ];
                        }),
                    ];
                });
            }),
            "escrows" =>$this->whenLoaded("escrows", function () {
                return $this->escrows->map(function ($escrow) {
                    return [
                        "user_id" => $this->escrow->user_id,
                        "operation_id" => $this->escrow->operation_id,
                        "montant" => $this->escrow->montant,
                        "statut" => $this->escrow->statut,
                        "raison_blocage" => $this->escrow->raison_blocage,
                        "libere_at" => $this->escrow->libere_at ? format_date_fr_chiffre($this->escrow->libere_at) : null,
                        "operation" => $this->whenLoaded('operation', function () {
                            return [
                                'uuid' => $this->operation->id,
                                'montant' => $this->operation->montant,
                                'type_operation' => $this->operation->type_operation,
                                'description' => $this->operation->description,
                                'statut' => $this->operation->statut,
                            ];
                        }),
                    ];
                });
            }),
            "operations" => $this->whenLoaded("operations", function () {
                return $this->operations->map(function ($operation) {
                    return [
                        "uuid"=> $this->operation->id,
                        "montant"=> $this->operation->montant,
                        "type_operation"=> $this->operation->type_operation,
                        "description"=> $this->operation->description,
                        "statut"=> $this->operation->statut,
                        "type_cotisation"=> $this->whenLoaded('type_cotisation', function () {
                            return [
                                'uuid' => $this->type_cotisation->id,
                                'libelle' => $this->type_cotisation->libelle,
                                'code' => $this->type_cotisation->code,
                                'categorie' => $this->type_cotisation->categorie,
                                'est_obligatoire' => $this->type_cotisation->est_obligatoire,
                                'est_actif' => $this->type_cotisation->est_actif,
                            ];
                        }) ?? '',
                        "objectif_epargne"=> $this->whenLoaded('objectif_epargne', function () {
                            return [
                                'uuid' => $this->objectif_epargne->id,
                                'libelle' => $this->objectif_epargne->libelle,
                                'montant_cible' => $this->objectif_epargne->montant_cible,
                                'montant_epargne' => $this->objectif_epargne->montant_epargne,
                                'date_limite' => format_date_fr_chiffre($this->objectif_epargne->date_limite),
                                'est_actif' => $this->objectif_epargne->est_actif,
                            ];
                        }) ?? "",
                        "paiement_entrant"=> $this->whenLoaded('paiement_entrant', function () {
                            return [
                                'uuid' => $this->paiement_entrant->id,
                                'montant_brut' => $this->paiement_entrant->montant_brut,
                                'statut' => $this->paiement_entrant->statut,
                                'reference_externe' => $this->paiement_entrant->reference_externe,
                                'operateur_source' => $this->paiement_entrant->operateur_source,
                                'qr_code_ref' => $this->paiement_entrant->qr_code_ref,
                            ];
                        }) ?? "",
                    ];
                });
            }),

            "paiementsEntrants" => $this->whenLoaded("paiementsEntrants", function () {
                return $this->paiementsEntrants->map(function ($paiement) {
                    return [
                        'uuid' => $this->paiement->id,
                        "user_id" => $this->paiement->user_id,
                        'montant_brut' => $this->paiement->montant_brut,
                        'statut' => $this->paiement->statut,
                        'reference_externe' => $this->paiement->reference_externe,
                        'operateur_source' => $this->paiement->operateur_source,
                        'qr_code_ref' => $this->paiement->qr_code_ref,
                        "operation" => $this->whenLoaded('operation', function () {
                            return [
                                'uuid' => $this->operation->id,
                                'montant' => $this->operation->montant,
                                'type_operation' => $this->operation->type_operation,
                                'description' => $this->operation->description,
                                'statut' => $this->operation->statut,
                                "compte_mobile_money" => $this->whenLoaded('compte_mobile_money', function () {
                                    return [
                                        'uuid' => $this->compte_mobile_money->id,
                                        'operateur' => $this->compte_mobile_money->operateur,
                                        'numero_compte' => $this->compte_mobile_money->numero_compte,
                                        'est_principal' => $this->compte_mobile_money->est_principal,
                                        'est_actif' => $this->compte_mobile_money->est_actif,
                                    ];
                                }),
                            ];
                        }),
                    ];
                });
            }),
            "reglePrelevements" => $this->whenLoaded("reglePrelevements", function () {
                return $this->reglePrelevements->map(function ($regle) {
                    return [
                        'uuid' => $this->regle->id,
                        "user_id" => $this->regle->user_id,
                        'type_calcul' => $this->regle->type_calcul,
                        'valeur' => $this->regle->valeur,
                        'est_actif' => $this->regle->est_actif,
                        'ordre_priorite' => $this->regle->ordre_priorite,
                        "type_cotisation_id" => $this->regle->type_cotisation_id,
                        "type_cotisation" => $this->whenLoaded('type_cotisation', function () {
                            return [
                                'uuid' => $this->type_cotisation->id,
                                'libelle' => $this->type_cotisation->libelle,
                                'code' => $this->type_cotisation->code,
                                'categorie' => $this->type_cotisation->categorie,
                                'est_obligatoire' => $this->type_cotisation->est_obligatoire,
                                'est_actif' => $this->type_cotisation->est_actif,                               
                            ];
                        }),
                    ];
                });
            }),




        ];
    }
}
