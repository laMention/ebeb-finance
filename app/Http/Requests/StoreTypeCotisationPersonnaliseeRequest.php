<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeCotisationPersonnaliseeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle'     => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/'],
            'categorie'   => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            // Source de vérité du suivi de conformité pour toute cotisation personnalisée — toujours requis.
            'montant_paiement_mensuel' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'  => 'Le libellé est obligatoire.',
            'libelle.string'    => 'Le libellé doit être une chaîne de caractères.',
            'libelle.max'       => 'Le libellé ne peut pas dépasser 255 caractères.',

            'code.required'     => 'Le code est obligatoire.',
            'code.string'       => 'Le code doit être une chaîne de caractères.',
            'code.max'          => 'Le code ne peut pas dépasser 50 caractères.',
            'code.regex'        => 'Le code doit contenir uniquement des lettres majuscules, des chiffres et des underscores.',

            'categorie.string'  => 'La catégorie doit être une chaîne de caractères.',
            'categorie.max'     => 'La catégorie ne peut pas dépasser 100 caractères.',

            'description.string' => 'La description doit être une chaîne de caractères.',
            'montant_paiement_mensuel.required' => 'Le montant du paiement mensuel est obligatoire pour une cotisation personnalisée (source de vérité du suivi de conformité).',
            'montant_paiement_mensuel.numeric' => 'Le montant du paiement mensuel doit être un nombre.',
            'montant_paiement_mensuel.min' => 'Le montant du paiement mensuel doit être supérieure ou égale à 0.',
        ];
    }
}
