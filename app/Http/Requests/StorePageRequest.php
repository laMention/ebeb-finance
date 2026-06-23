<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'titre'            => ['required', 'string', 'max:255'],
            'contenu'          => ['nullable', 'string'],
            'statut'           => ['nullable', 'in:BROUILLON,PUBLIE'],
            'type_page'        => ['nullable', 'string', 'max:60'],
            'meta_titre'       => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'ordre'            => ['nullable', 'integer', 'min:0', 'max:9999'],
            'publie_le'        => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required'   => 'Le titre de la page est obligatoire.',
            'titre.max'        => 'Le titre ne peut pas dépasser 255 caractères.',
            'statut.in'        => 'Le statut doit être BROUILLON ou PUBLIE.',
            'meta_titre.max'   => 'Le méta-titre ne peut pas dépasser 160 caractères.',
            'meta_description.max' => 'La méta-description ne peut pas dépasser 320 caractères.',
            'ordre.integer'    => 'L\'ordre doit être un entier.',
            'ordre.min'        => 'L\'ordre doit être supérieur ou égal à 0.',
        ];
    }
}
