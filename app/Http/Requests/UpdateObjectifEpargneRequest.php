<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateObjectifEpargneRequest extends FormRequest
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
            'libelle'        => ['sometimes', 'string', 'max:255'],
            'montant_cible'  => ['sometimes', 'numeric', 'min:1'],
            'date_limite'    => ['sometimes', 'nullable', 'date', 'after:today'],
            'montant_epargne'=> ['sometimes', 'numeric', 'min:0'],
            'type_calcul'    => ['sometimes', 'nullable', 'string', 'in:FIXE,POURCENTAGE'],
            'valeur'         => ['sometimes', 'nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.max'            => "Le libellé ne doit pas dépasser :max caractères.",
            'montant_cible.numeric'  => "Le montant cible doit être un nombre.",
            'montant_cible.min'      => "Le montant cible doit être supérieur à zéro.",
            'date_limite.date'       => "La date limite doit être une date valide.",
            'date_limite.after'      => "La date limite doit être une date future.",
            'montant_epargne.numeric'=> "Le montant épargné doit être un nombre.",
            'montant_epargne.min'    => "Le montant épargné ne peut pas être négatif.",
            'type_calcul.in'         => "Le mode de prélèvement doit être FIXE ou POURCENTAGE.",
            'valeur.numeric'         => "La valeur du prélèvement doit être un nombre.",
            'valeur.min'             => "La valeur du prélèvement doit être supérieure à zéro.",
        ];
    }
}
