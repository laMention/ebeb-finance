<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfigurerReglesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'regles'                => ['required', 'array', 'min:0'],
            'regles.*.type_cotisation_id' => ['required', 'uuid', 'exists:type_cotisations,id'],
            'regles.*.type_calcul'        => ['required', 'string', 'in:FIXE,POURCENTAGE'],
            'regles.*.valeur'             => ['required', 'numeric', 'min:0'],
            'regles.*.est_actif'          => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'regles.required' => 'Le tableau des règles est obligatoire.',
            'regles.array'    => 'Les règles doivent être un tableau.',
            'regles.min'      => 'Au moins une règle est requise.',

            'regles.*.type_cotisation_id.required' => 'Le type de cotisation est obligatoire pour chaque règle.',
            'regles.*.type_cotisation_id.uuid'    => 'L\'ID du type de cotisation doit être un UUID valide.',
            'regles.*.type_cotisation_id.exists'  => 'Ce type de cotisation n\'existe pas.',

            'regles.*.type_calcul.required' => 'Le type de calcul est obligatoire pour chaque règle.',
            'regles.*.type_calcul.in'       => 'Le type de calcul doit être FIXE ou POURCENTAGE.',

            'regles.*.valeur.required' => 'La valeur est obligatoire pour chaque règle.',
            'regles.*.valeur.numeric'  => 'La valeur doit être un nombre.',
            'regles.*.valeur.min'      => 'La valeur doit être positive.',

            'regles.*.est_actif.boolean' => 'Le champ est_actif doit être vrai ou faux.',
        ];
    }
}
