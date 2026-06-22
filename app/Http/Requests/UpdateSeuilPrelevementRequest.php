<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeuilPrelevementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seuil_pourcentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'seuil_montant'     => ['nullable', 'numeric', 'min:0'],
            'est_actif'         => ['sometimes', 'boolean'],
            'description'       => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'seuil_pourcentage.max' => 'Le seuil en pourcentage ne peut pas dépasser 100 %.',
            'seuil_pourcentage.min' => 'Le seuil en pourcentage doit être positif.',
            'seuil_montant.min'     => 'Le seuil en montant doit être positif.',
        ];
    }
}
