<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RembourserOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif'   => ['required', 'string', 'min:5'],
            'montant' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif du remboursement est obligatoire.',
            'motif.min'      => 'Le motif doit contenir au moins :min caractères.',
            'montant.numeric'=> 'Le montant doit être un nombre.',
            'montant.min'    => 'Le montant doit être supérieur à 0.',
        ];
    }
}
