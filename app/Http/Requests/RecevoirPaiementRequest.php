<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RecevoirPaiementRequest extends FormRequest
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
            'reference_externe'      => ['required', 'string', 'max:255', 'unique:paiement_entrants,reference_externe'],
            'montant_brut'           => ['required', 'numeric', 'min:1'],
            // 'operateur_source'       => ['required', 'string', 'max:50'],
            'qr_code_ref'            => ['nullable', 'string', 'max:255'],
            // 'compte_mobile_money_id' => ['required_without:qr_code_ref', 'nullable', 'uuid', 'exists:compte_mobile_moneys,id'],
            'description'            => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference_externe.required' => "La référence externe du paiement est obligatoire.",
            'reference_externe.unique'   => "Ce paiement a déjà été traité (référence en double).",
            'montant_brut.required'      => "Le montant brut est obligatoire.",
            'montant_brut.numeric'       => "Le montant brut doit être un nombre.",
            'montant_brut.min'           => "Le montant brut doit être supérieur à zéro.",
            // 'operateur_source.required'  => "L'opérateur source est obligatoire.",
            // 'compte_mobile_money_id.required_without' => "Le compte Mobile Money ou le QR Code de référence est requis.",
            // 'compte_mobile_money_id.exists' => "Le compte Mobile Money indiqué est introuvable.",
        ];
    }
}
