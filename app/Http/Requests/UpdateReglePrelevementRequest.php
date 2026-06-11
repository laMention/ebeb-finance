<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReglePrelevementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_cotisation_id' => ['sometimes', 'uuid', 'exists:type_cotisations,id'],
            'type_calcul'        => ['sometimes', 'string', 'in:FIXE,POURCENTAGE'],
            'valeur'             => ['sometimes', 'numeric', 'min:0'],
            'est_actif'          => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_cotisation_id.uuid'   => 'L\'ID du type de cotisation doit être un UUID valide.',
            'type_cotisation_id.exists' => 'Ce type de cotisation n\'existe pas.',

            'type_calcul.in' => 'Le type de calcul doit être FIXE ou POURCENTAGE.',

            'valeur.numeric' => 'La valeur doit être un nombre.',
            'valeur.min'     => 'La valeur doit être positive.',

            'est_actif.boolean' => 'Le champ est_actif doit être vrai ou faux.',
        ];
    }
}
