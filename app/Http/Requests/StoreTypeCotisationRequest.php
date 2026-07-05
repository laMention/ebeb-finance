<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTypeCotisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'libelle'         => ['required', 'string', 'max:255'],
            'code'            => ['required', 'string', 'max:50', 'unique:type_cotisations,code'],
            'categorie'                       => ['required', 'string', 'max:100'],
            'est_obligatoire'                 => ['boolean'],
            'est_actif'                       => ['boolean'],
            'default_type_calcul'             => ['nullable', 'string', 'in:FIXE,POURCENTAGE'],
            'default_valeur'                  => ['nullable', 'numeric', 'min:0'],
            'default_est_actif'               => ['nullable', 'boolean'],
            'default_date_entree_en_vigueur'  => ['nullable', 'date'],
            'description'                     => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'   => 'Le libellé est obligatoire.',
            'libelle.string'     => 'Le libellé doit être une chaîne de caractères.',
            'libelle.max'        => 'Le libellé ne peut pas dépasser 255 caractères.',

            'code.required'      => 'Le code est obligatoire.',
            'code.string'        => 'Le code doit être une chaîne de caractères.',
            'code.max'           => 'Le code ne peut pas dépasser 50 caractères.',
            'code.unique'        => 'Ce code est déjà utilisé par un autre type de cotisation.',

            'categorie.required' => 'La catégorie est obligatoire.',
            'categorie.string'   => 'La catégorie doit être une chaîne de caractères.',
            'categorie.max'      => 'La catégorie ne peut pas dépasser 100 caractères.',

            'est_obligatoire.boolean' => 'Le champ est_obligatoire doit être vrai ou faux.',
            'est_actif.boolean'       => 'Le champ est_actif doit être vrai ou faux.',
            'default_type_calcul.in'  => 'Le type de calcul par défaut doit être FIXE ou POURCENTAGE.',
            'default_valeur.numeric'  => 'La valeur par défaut doit être un nombre.',
            'default_valeur.min'      => 'La valeur par défaut doit être positive.',
            'default_est_actif.boolean' => 'Le champ default_est_actif doit être vrai ou faux.',
            'default_date_entree_en_vigueur.date' => 'La date d\'entrée en vigueur doit être une date valide.',

            'description.string' => 'La description doit être une chaîne de caractères.',
        ];
    }
}
