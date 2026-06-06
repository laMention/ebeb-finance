<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // IDENTITE DU TRAVAILLEUR
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'numero_cnps' => ['nullable', 'string','max:255'],
            'numero_cmu' => ['nullable', 'string','max:255'],
            'situation_familiale' => ['required', 'string',], //
            'sexe' => ['nullable', 'string'],
            'date_naissance' => ['required', 'date'],

            // INFORMATIONS PERSONNELLES            
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            // 'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'lieu_naissance' => ['nullable', 'string','max:255'],
            'profession' => ['nullable', 'string','max:255'], //ACTIVITE
            'telephone' => ['required', 'string','min:8','max:10'],
            // 'statut' => ['required', 'string','min:8','max:10'],
            // 'type_carte' => ['nullable', 'string','min:8','max:10'],
            // 'pays' => ['required', 'string','min:8','max:10'],
            'ville' => ['required', 'string'],
            'quartier' => ['required', 'string'],
            'village' => ['nullable', 'string'], //COMMMUNE SOUS PREFECTURE
            'adresse_postale' => ['nullable', 'string'],
    
            // DECLARATION REVENU
            'montant_revenu' => ['required', 'numeric'],
            'montant_cotisation_regime_base' => ['required', 'numeric'],
            'montant_cotisation_regime_complementaire' => ['required', 'numeric'],
            'montant_cotisation_mensuelle' => ['required', 'numeric'],
            'montant_cotisation_trimestrielle' => ['required', 'numeric'],

            // INFORMATIONS PROFESSIONNELLES
            'categorie_professionnelle' => ['required', 'string'],
            'metier' => ['required', 'string'],
            'date_debut_activite' => ['required', 'date'],
            'ville_activite' => ['required', 'string'],
            'quartier_activite' => ['required', 'string'],
            'commune_sous_prefecture_activite' => ['required', 'string'],

            // DOCUMENTS KYC
            'type_document'=> ['required', 'string'],
            'numero_document'=> ['required', 'string'],
            'document_etablie_le'=> ['required', 'date'],
            'document_expire_le'=> ['required', 'date'],
            'url_recto'=> ['required', 'file','image','mimes:jpeg,png,jpg,gif,svg|max:2048'],
            'url_verso'=> ['required', 'file','image','mimes:jpeg,png,jpg,gif,svg|max:2048'],
            'url_selfie'=> ['nullable', 'file','image','mimes:jpeg,png,jpg,gif,svg|max:2048'],
        ];
    }

    // Messages
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.string' => 'Le nom doit être une chaîne de caractères.',
            'nom.max' => 'Le nom ne doit pas dépasser :max caractères.',

            'prenom.required' => 'Le prénom est obligatoire.',
            'prenom.string' => 'Le prénom doit être une chaîne de caractères.',
            'prenom.max' => 'Le prénom ne doit pas dépasser :max caractères.',

            'numero_cnps.string' => 'Le numéro CNPS doit être une chaîne de caractères.',
            'numero_cnps.max' => 'Le numéro CNPS ne doit pas dépasser :max caractères.',

            'numero_cmu.string' => 'Le numéro CMU doit être une chaîne de caractères.',
            'numero_cmu.max' => 'Le numéro CMU ne doit pas dépasser :max caractères.',

            'situation_familiale.required' => 'La situation familiale est obligatoire.',
            'situation_familiale.string' => 'La situation familiale doit être une chaîne de caractères.',

            'sexe.string' => 'Le sexe doit être une chaîne de caractères.',

            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.date' => 'La date de naissance doit être une date valide.',

            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.string' => 'L’adresse e-mail doit être une chaîne de caractères.',
            'email.email' => 'L’adresse e-mail doit être une adresse valide.',
            'email.max' => 'L’adresse e-mail ne doit pas dépasser :max caractères.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',

            'lieu_naissance.string' => 'Le lieu de naissance doit être une chaîne de caractères.',
            'lieu_naissance.max' => 'Le lieu de naissance ne doit pas dépasser :max caractères.',

            'profession.string' => 'La profession doit être une chaîne de caractères.',
            'profession.max' => 'La profession ne doit pas dépasser :max caractères.',

            'telephone.required' => 'Le téléphone est obligatoire.',
            'telephone.string' => 'Le téléphone doit être une chaîne de caractères.',
            'telephone.min' => 'Le téléphone doit contenir au moins :min caractères.',
            'telephone.max' => 'Le téléphone ne doit pas dépasser :max caractères.',

            'ville.required' => 'La ville est obligatoire.',
            'ville.string' => 'La ville doit être une chaîne de caractères.',

            'quartier.required' => 'Le quartier est obligatoire.',
            'quartier.string' => 'Le quartier doit être une chaîne de caractères.',

            'village.string' => 'Le village doit être une chaîne de caractères.',

            'adresse_postale.string' => 'L’adresse postale doit être une chaîne de caractères.',

            'montant_revenu.required' => 'Le montant du revenu est obligatoire.',
            'montant_revenu.numeric' => 'Le montant du revenu doit être un nombre.',

            'montant_cotisation_regime_base.required' => 'Le montant de la cotisation du régime de base est obligatoire.',
            'montant_cotisation_regime_base.numeric' => 'Le montant de la cotisation du régime de base doit être un nombre.',

            'montant_cotisation_regime_complementaire.required' => 'Le montant de la cotisation du régime complémentaire est obligatoire.',
            'montant_cotisation_regime_complementaire.numeric' => 'Le montant de la cotisation du régime complémentaire doit être un nombre.',

            'montant_cotisation_mensuelle.required' => 'Le montant de la cotisation mensuelle est obligatoire.',
            'montant_cotisation_mensuelle.numeric' => 'Le montant de la cotisation mensuelle doit être un nombre.',

            'montant_cotisation_trimestrielle.required' => 'Le montant de la cotisation trimestrielle est obligatoire.',
            'montant_cotisation_trimestrielle.numeric' => 'Le montant de la cotisation trimestrielle doit être un nombre.',

            'categorie_professionnelle.required' => 'La catégorie professionnelle est obligatoire.',
            'categorie_professionnelle.string' => 'La catégorie professionnelle doit être une chaîne de caractères.',

            'metier.required' => 'Le métier est obligatoire.',
            'metier.string' => 'Le métier doit être une chaîne de caractères.',

            'date_debut_activite.required' => 'La date de début d’activité est obligatoire.',
            'date_debut_activite.date' => 'La date de début d’activité doit être une date valide.',

            'ville_activite.required' => 'La ville d’activité est obligatoire.',
            'ville_activite.string' => 'La ville d’activité doit être une chaîne de caractères.',

            'quartier_activite.required' => 'Le quartier d’activité est obligatoire.',
            'quartier_activite.string' => 'Le quartier d’activité doit être une chaîne de caractères.',

            'commune_sous_prefecture_activite.required' => 'La commune / sous-préfecture d’activité est obligatoire.',
            'commune_sous_prefecture_activite.string' => 'La commune / sous-préfecture d’activité doit être une chaîne de caractères.',

            'type_document.required' => 'Le type de document est obligatoire.',
            'type_document.string' => 'Le type de document doit être une chaîne de caractères.',

            'numero_document.required' => 'Le numéro de document est obligatoire.',
            'numero_document.string' => 'Le numéro de document doit être une chaîne de caractères.',

            'document_etablie_le.required' => 'La date d’émission du document est obligatoire.',
            'document_etablie_le.date' => 'La date d’émission du document doit être une date valide.',

            'document_expire_le.required' => 'La date d’expiration du document est obligatoire.',
            'document_expire_le.date' => 'La date d’expiration du document doit être une date valide.',

            'url_recto.required' => 'L’URL du recto est obligatoire.',
            'url_recto.string' => 'L’URL du recto doit être une chaîne de caractères.',

            'url_verso.required' => 'L’URL du verso est obligatoire.',
            'url_verso.string' => 'L’URL du verso doit être une chaîne de caractères.',

            'url_selfie.string' => 'L’URL du selfie doit être une chaîne de caractères.',
        ];
    }
}
