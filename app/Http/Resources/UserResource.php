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
            "prenom" => $this->prenom,
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
            "situation_familiale" => $this->situation_familiale ?? mettre_en_majuscule('célibataire'),
            "nombre_enfants" => $this->nombre_enfants ?? 0,
            "date_activation" => format_date_fr_chiffre($this->date_activation),
            "created_at" => format_date_fr_chiffre($this->created_at),
            "derniere_connexion" => format_date_fr_chiffre($this->derniere_connexion),
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
                        'uuid' => $mobile_money->id,
                        'operateur' => $mobile_money->operateur,
                        'numero_compte' => $mobile_money->numero_compte,
                        'est_principal' => $mobile_money->est_principal,
                        'est_actif' => $mobile_money->est_actif,
                    ];
                });
            }),
            "enfants" => $this->whenLoaded("enfants", function () {
                return $this->enfants->map(function ($enfant) {
                    return [
                        "nom" => $enfant->nom,
                        "prenom" => $enfant->prenom,
                        "date_naissance" => $enfant->date_naissance,
                        "lieu_naissance" => $enfant->lieu_naissance,
                    ];
                });
            }),
            "cotisations" => $this->whenLoaded("cotisations", function () {
                return $this->cotisations->map(function ($cotisation) {
                    $tc = $cotisation->typeCotisation;
                    return [
                        "mois"             => $cotisation->mois,
                        "annee"            => $cotisation->annee,
                        "montant_verse"    => $cotisation->montant_verse,
                        "montant_objectif" => $cotisation->montant_objectif,
                        "statut"           => $cotisation->statut,
                        "numero_adherent"  => $cotisation->numero_adherent,
                        "date_paiement"    => $cotisation->date_paiement,
                        "typeCotisation"   => $tc ? [
                            'uuid'            => $tc->id,
                            'libelle'         => $tc->libelle,
                            'code'            => $tc->code,
                            'categorie'       => $tc->categorie,
                            'est_obligatoire' => $tc->est_obligatoire,
                            'est_actif'       => $tc->est_actif,
                        ] : null,
                    ];
                });
            }),
            "escrows" => $this->whenLoaded("escrows", function () {
                return $this->escrows->map(function ($escrow) {
                    $op = $escrow->operation;
                    return [
                        "user_id"        => $escrow->user_id,
                        "operation_id"   => $escrow->operation_id,
                        "montant"        => $escrow->montant,
                        "statut"         => $escrow->statut,
                        "raison_blocage" => $escrow->raison_blocage,
                        "libere_at"      => $escrow->libere_at ? format_date_fr_chiffre($escrow->libere_at) : null,
                        "operation"      => $op ? [
                            'uuid'           => $op->id,
                            'montant'        => $op->montant,
                            'type_operation' => $op->type_operation,
                            'description'    => $op->description,
                            'statut'         => $op->statut,
                        ] : null,
                    ];
                });
            }),
            "operations" => $this->whenLoaded("operations", function () {
                return $this->operations->map(function ($operation) {
                    $tc = $operation->type_cotisation;
                    $oe = $operation->objectif_epargne;
                    $pe = $operation->paiement_entrant;
                    return [
                        "uuid"             => $operation->id,
                        "montant"          => $operation->montant,
                        "type_operation"   => $operation->type_operation,
                        "description"      => $operation->description,
                        "statut"           => $operation->statut,
                        "type_cotisation"  => $tc ? [
                            'uuid'            => $tc->id,
                            'libelle'         => $tc->libelle,
                            'code'            => $tc->code,
                            'categorie'       => $tc->categorie,
                            'est_obligatoire' => $tc->est_obligatoire,
                            'est_actif'       => $tc->est_actif,
                        ] : null,
                        "objectif_epargne" => $oe ? [
                            'uuid'            => $oe->id,
                            'libelle'         => $oe->libelle,
                            'montant_cible'   => $oe->montant_cible,
                            'montant_epargne' => $oe->montant_epargne,
                            'date_limite'     => format_date_fr_chiffre($oe->date_limite),
                            'est_actif'       => $oe->est_actif,
                        ] : null,
                        "paiement_entrant" => $pe ? [
                            'uuid'              => $pe->id,
                            'montant_brut'      => $pe->montant_brut,
                            'statut'            => $pe->statut,
                            'reference_externe' => $pe->reference_externe,
                            'operateur_source'  => $pe->operateur_source,
                            'qr_code_ref'       => $pe->qr_code_ref,
                        ] : null,
                    ];
                });
            }),

            "paiementsEntrants" => $this->whenLoaded("paiementsEntrants", function () {
                return $this->paiementsEntrants->map(function ($paiement) {
                    $op  = $paiement->operation;
                    $cmm = $op?->compte_mobile_money ?? null;
                    return [
                        'uuid'              => $paiement->id,
                        "user_id"           => $paiement->user_id,
                        'montant_brut'      => $paiement->montant_brut,
                        'statut'            => $paiement->statut,
                        'reference_externe' => $paiement->reference_externe,
                        'operateur_source'  => $paiement->operateur_source,
                        'qr_code_ref'       => $paiement->qr_code_ref,
                        "operation"         => $op ? [
                            'uuid'           => $op->id,
                            'montant'        => $op->montant,
                            'type_operation' => $op->type_operation,
                            'description'    => $op->description,
                            'statut'         => $op->statut,
                            "compte_mobile_money" => $cmm ? [
                                'uuid'          => $cmm->id,
                                'operateur'     => $cmm->operateur,
                                'numero_compte' => $cmm->numero_compte,
                                'est_principal' => $cmm->est_principal,
                                'est_actif'     => $cmm->est_actif,
                            ] : null,
                        ] : null,
                    ];
                });
            }),
            "reglePrelevements" => $this->whenLoaded("reglePrelevements", function () {
                return $this->reglePrelevements->map(function ($regle) {
                    $tc = $regle->type_cotisation;
                    return [
                        'uuid'               => $regle->id,
                        "user_id"            => $regle->user_id,
                        'type_calcul'        => $regle->type_calcul,
                        'valeur'             => $regle->valeur,
                        'est_actif'          => $regle->est_actif,
                        'ordre_priorite'     => $regle->ordre_priorite,
                        "type_cotisation_id" => $regle->type_cotisation_id,
                        "type_cotisation"    => $tc ? [
                            'uuid'            => $tc->id,
                            'libelle'         => $tc->libelle,
                            'code'            => $tc->code,
                            'categorie'       => $tc->categorie,
                            'est_obligatoire' => $tc->est_obligatoire,
                            'est_actif'       => $tc->est_actif,
                        ] : null,
                    ];
                });
            }),

        ];
    }
}
