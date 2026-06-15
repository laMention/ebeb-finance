<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfilRequest extends FormRequest
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
            'email'              => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'ville'              => ['sometimes', 'string', 'max:255'],
            'quartier'           => ['sometimes', 'string', 'max:255'],
            'situation_familiale'=> ['sometimes', 'string', 'max:100'],
            'nombre_enfants'     => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email'              => "L'adresse e-mail doit être une adresse valide.",
            'email.unique'             => "Cette adresse e-mail est déjà utilisée par un autre compte.",
            'ville.string'             => "La ville doit être une chaîne de caractères.",
            'quartier.string'          => "Le quartier doit être une chaîne de caractères.",
            'situation_familiale.string' => "La situation familiale doit être une chaîne de caractères.",
            'nombre_enfants.integer'   => "Le nombre d'enfants doit être un entier.",
            'nombre_enfants.min'       => "Le nombre d'enfants ne peut pas être négatif.",
        ];
    }
}
