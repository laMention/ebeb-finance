<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMoyenPaiementRequest extends FormRequest
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
            'libelle'    => ['required', 'string', 'max:255'],
            'code'       => ['required', 'string', 'max:50', 'unique:moyen_paiements,code'],
            'logo'       => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp'],
            'operateur'  => ['required', 'string', 'max:100'],
            'par_defaut' => ['boolean'],
            'est_actif'  => ['boolean'],
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
            'code.unique'       => 'Ce code est déjà utilisé par un autre moyen de paiement.',

            'logo.image'  => 'Le logo doit être une image.',
            'logo.mimes'  => "Format d'image accepté : png, jpg, jpeg, webp, svg.",

            'operateur.required' => "L'opérateur est obligatoire.",
            'operateur.string'   => "L'opérateur doit être une chaîne de caractères.",
            'operateur.max'      => "L'opérateur ne peut pas dépasser 100 caractères.",

            'par_defaut.boolean' => 'Le champ par_defaut doit être vrai ou faux.',
            'est_actif.boolean'  => 'Le champ est_actif doit être vrai ou faux.',
        ];
    }
}
