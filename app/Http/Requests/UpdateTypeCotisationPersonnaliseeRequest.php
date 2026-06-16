<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTypeCotisationPersonnaliseeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle'     => ['sometimes', 'string', 'max:255'],
            'code'        => ['sometimes', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/'],
            'description' => ['nullable', 'string'],
            'est_actif'   => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.string'    => 'Le libellé doit être une chaîne de caractères.',
            'libelle.max'       => 'Le libellé ne peut pas dépasser 255 caractères.',

            'code.string'       => 'Le code doit être une chaîne de caractères.',
            'code.max'          => 'Le code ne peut pas dépasser 50 caractères.',
            'code.regex'        => 'Le code doit contenir uniquement des lettres majuscules, des chiffres et des underscores.',

            'description.string' => 'La description doit être une chaîne de caractères.',
            'est_actif.boolean'  => 'Le champ est_actif doit être vrai ou faux.',
        ];
    }
}
