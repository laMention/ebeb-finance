<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EnvoyerTransfertQrRequest extends FormRequest
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
            'qr_scanne'        => ['required', 'string'],
            'compte_source_id' => ['required', 'uuid', 'exists:compte_mobile_moneys,id'],
            'montant'          => ['required', 'numeric', 'min:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'qr_scanne.required'        => 'Le contenu du QR code scanné est obligatoire.',
            'compte_source_id.required' => 'Le compte à débiter est obligatoire.',
            'compte_source_id.exists'   => 'Ce compte mobile money est introuvable.',
            'montant.required'          => 'Le montant est obligatoire.',
            'montant.numeric'           => 'Le montant doit être un nombre.',
            'montant.min'               => 'Le montant minimum est de 100 FCFA.',
        ];
    }
}
