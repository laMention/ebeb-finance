<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypeCotisationRequest extends FormRequest
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
        $typeCotisationId = $this->route('typeCotisation')?->id;

        return [
            'libelle'         => ['sometimes', 'string', 'max:255'],
            'code'            => ['sometimes', 'string', 'max:50', Rule::unique('type_cotisations', 'code')->ignore($typeCotisationId)],
            'categorie'                       => ['sometimes', 'string', 'max:100'],
            'est_obligatoire'                 => ['sometimes', 'boolean'],
            'est_actif'                       => ['sometimes', 'boolean'],
            'default_type_calcul'             => ['sometimes', 'nullable', 'string', 'in:FIXE,POURCENTAGE'],
            'default_valeur'                  => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'default_est_actif'               => ['sometimes', 'nullable', 'boolean'],
            'default_date_entree_en_vigueur'  => ['sometimes', 'nullable', 'date'],
            'description'                     => ['nullable', 'string'],
            'montant_paiement_mensuel'        => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Vérifie que montant_paiement_mensuel reste toujours renseigné pour une cotisation AMU —
     * source de vérité du suivi de conformité — en tenant compte de l'état actuel du modèle
     * (categorie/montant_paiement_mensuel peuvent ne pas faire partie de cette requête partielle).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->route('typeCotisation');

            $categorieFinale = $this->has('categorie')
                ? strtoupper((string) $this->input('categorie'))
                : strtoupper((string) ($type?->categorie ?? ''));

            if ($categorieFinale !== 'AMU') {
                return;
            }

            $montantFinal = $this->has('montant_paiement_mensuel')
                ? $this->input('montant_paiement_mensuel')
                : $type?->montant_paiement_mensuel;

            if (empty($montantFinal)) {
                $validator->errors()->add(
                    'montant_paiement_mensuel',
                    'Le montant du paiement mensuel est obligatoire pour une cotisation AMU (source de vérité du suivi de conformité).',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'libelle.string'     => 'Le libellé doit être une chaîne de caractères.',
            'libelle.max'        => 'Le libellé ne peut pas dépasser 255 caractères.',

            'code.string'        => 'Le code doit être une chaîne de caractères.',
            'code.max'           => 'Le code ne peut pas dépasser 50 caractères.',
            'code.unique'        => 'Ce code est déjà utilisé par un autre type de cotisation.',

            'categorie.string'   => 'La catégorie doit être une chaîne de caractères.',
            'categorie.max'      => 'La catégorie ne peut pas dépasser 100 caractères.',

            'est_obligatoire.boolean' => 'Le champ est_obligatoire doit être vrai ou faux.',
            'est_actif.boolean'       => 'Le champ est_actif doit être vrai ou faux.',
            'default_type_calcul.in'  => 'Le type de calcul par défaut doit être FIXE ou POURCENTAGE.',
            'default_valeur.numeric'  => 'La valeur par défaut doit être un nombre.',
            'default_valeur.min'      => 'La valeur par défaut doit être positive.',
            'default_est_actif.boolean' => 'Le champ default_est_actif doit être vrai ou faux.',
            'default_date_entree_en_vigueur.date' => 'La date d\'entrée en vigueur doit être une date valide.',

            'description.string' => 'La description doit être une chaîne de caractères.',
            'montant_paiement_mensuel.numeric' => 'Le montant du paiement mensuel doit être un nombre.',
            'montant_paiement_mensuel.min' => 'Le montant du paiement mensuel doit être supérieure ou égale à 0.',
        ];
    }
}
