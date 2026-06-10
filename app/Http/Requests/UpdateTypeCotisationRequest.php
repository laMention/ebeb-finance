<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypeCotisationRequest extends FormRequest
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
        $typeCotisationId = $this->route('typeCotisation')?->id;

        return [
            'libelle'         => ['sometimes', 'string', 'max:255'],
            'code'            => ['sometimes', 'string', 'max:50', Rule::unique('type_cotisations', 'code')->ignore($typeCotisationId)],
            'categorie'       => ['sometimes', 'string', 'max:100'],
            'est_obligatoire' => ['sometimes', 'boolean'],
            'est_actif'       => ['sometimes', 'boolean'],
            'description'     => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.string'     => 'Le libellé doit être une chaîne de caractères.',
            'libelle.max'        => 'Le libellé ne peut pas dépasser 255 caractères.',

            'code.string'        => 'Le code doit être une chaîne de caractères.',
            'code.max'           => 'Le code ne peut pas dépasser 50 caractères.',
            'code.unique'        => 'Ce code est déjà utilisé par un autre type de cotisation.',

            'categorie.string'   => 'La catégorie doit être une chaîne de caractères.',
            'categorie.max'      => 'La catégorie ne peut pas dépasser 100 caractères.',

            'est_obligatoire.boolean' => 'Le champ est_obligatoire doit être vrai ou faux.',
            'est_actif.boolean'       => 'Le champ est_actif doit être vrai ou faux.',

            'description.string' => 'La description doit être une chaîne de caractères.',
        ];
    }
}
