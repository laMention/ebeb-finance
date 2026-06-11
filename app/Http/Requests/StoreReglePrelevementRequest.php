<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReglePrelevementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_cotisation_id' => ['required', 'uuid', 'exists:type_cotisations,id'],
            'type_calcul'        => ['required', 'string', 'in:FIXE,POURCENTAGE'],
            'valeur'             => ['required', 'numeric', 'min:0'],
            'est_actif'          => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_cotisation_id.required' => 'Le type de cotisation est obligatoire.',
            'type_cotisation_id.uuid'    => 'L\'ID du type de cotisation doit être un UUID valide.',
            'type_cotisation_id.exists'  => 'Ce type de cotisation n\'existe pas.',

            'type_calcul.required' => 'Le type de calcul est obligatoire.',
            'type_calcul.in'       => 'Le type de calcul doit être FIXE ou POURCENTAGE.',

            'valeur.required' => 'La valeur est obligatoire.',
            'valeur.numeric'  => 'La valeur doit être un nombre.',
            'valeur.min'      => 'La valeur doit être positive.',

            'est_actif.boolean' => 'Le champ est_actif doit être vrai ou faux.',
        ];
    }
}
