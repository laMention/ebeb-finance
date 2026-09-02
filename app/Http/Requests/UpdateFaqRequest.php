<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFaqRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'question'  => ['sometimes', 'string', 'max:500'],
            'reponse'   => ['sometimes', 'string'],
            'categorie' => ['nullable', 'string', 'max:100'],
            'statut'    => ['nullable', 'in:BROUILLON,PUBLIE'],
            'ordre'     => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.in' => 'Le statut doit être BROUILLON ou PUBLIE.',
        ];
    }
}
