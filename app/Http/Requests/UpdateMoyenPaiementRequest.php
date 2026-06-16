<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMoyenPaiementRequest extends FormRequest
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
        $moyenPaiementId = $this->route('moyenPaiement')?->id;

        return [
            'libelle'    => ['sometimes', 'string', 'max:255'],
            'code'       => ['sometimes', 'string', 'max:50', Rule::unique('moyen_paiements', 'code')->ignore($moyenPaiementId)],
            'logo'       => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp'],
            'operateur'  => ['sometimes', 'string', 'max:100'],
            'est_actif'  => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.string'    => 'Le libellé doit être une chaîne de caractères.',
            'libelle.max'       => 'Le libellé ne peut pas dépasser 255 caractères.',

            'code.string'       => 'Le code doit être une chaîne de caractères.',
            'code.max'          => 'Le code ne peut pas dépasser 50 caractères.',
            'code.unique'       => 'Ce code est déjà utilisé par un autre moyen de paiement.',

            'logo.string'       => 'Le logo doit être une chaîne de caractères.',
            'logo.max'          => 'Le logo ne peut pas dépasser 500 caractères.',

            'operateur.string'  => "L'opérateur doit être une chaîne de caractères.",
            'operateur.max'     => "L'opérateur ne peut pas dépasser 100 caractères.",

            'est_actif.boolean' => 'Le champ est_actif doit être vrai ou faux.',
        ];
    }
}
