<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IdentifierDestinataireQrRequest extends FormRequest
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
            // Contenu brut scanné (JSON généré par CompteMobileMoneyService::genererQrCode) —
            // jamais interprété côté mobile, uniquement décodé et vérifié côté serveur.
            'qr_scanne'        => ['required', 'string'],
            'compte_source_id' => ['required', 'uuid', 'exists:compte_mobile_moneys,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'qr_scanne.required'        => 'Le contenu du QR code scanné est obligatoire.',
            'compte_source_id.required' => 'Le compte à débiter est obligatoire.',
            'compte_source_id.exists'   => 'Ce compte mobile money est introuvable.',
        ];
    }
}
