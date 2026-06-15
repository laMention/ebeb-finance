<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreObjectifEpargneRequest extends FormRequest
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
            'libelle'        => ['required', 'string', 'max:255'],
            'montant_cible'  => ['required', 'numeric', 'min:1'],
            'date_limite'    => ['nullable', 'date', 'after:today'],
            'montant_epargne'=> ['nullable', 'numeric', 'min:0'],
            'type_calcul'    => ['nullable', 'string', 'in:FIXE,POURCENTAGE'],
            'valeur'         => ['nullable', 'numeric', 'min:0.01'],
            // 'est_actif'      => ['boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'       => "Le libellé de l'objectif est obligatoire.",
            'libelle.max'            => "Le libellé ne doit pas dépasser :max caractères.",
            'montant_cible.required' => "Le montant cible est obligatoire.",
            'montant_cible.numeric'  => "Le montant cible doit être un nombre.",
            'montant_cible.min'      => "Le montant cible doit être supérieur à zéro.",
            'date_limite.date'       => "La date limite doit être une date valide.",
            'date_limite.after'      => "La date limite doit être une date future.",
            'montant_epargne.numeric'=> "Le montant épargné doit être un nombre.",
            'montant_epargne.min'    => "Le montant épargné ne peut pas être négatif.",
            'type_calcul.in'         => "Le mode de prélèvement doit être FIXE ou POURCENTAGE.",
            'valeur.numeric'         => "La valeur du prélèvement doit être un nombre.",
            'valeur.min'             => "La valeur du prélèvement doit être supérieure à zéro.",
            // 'est_actif.boolean'      => 'Le champ est_principal doit être vrai ou faux.'

        ];
    }
}
