<?php

namespace App\Http\Requests\ControlPanel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangerEtatPlateformeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'statut'       => 'required|string|in:ACTIVE,MAINTENANCE,DESACTIVEE',
            'message'      => 'nullable|string|max:1000',
            'motif'        => 'nullable|string|max:1000',
            'date_debut'   => 'nullable|date',
            'date_fin'     => 'nullable|date|after:date_debut',
            'confirmation' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'statut.required' => 'Le statut de la plateforme est obligatoire.',
            'statut.in'       => 'Statut invalide. Valeurs autorisées : ACTIVE, MAINTENANCE, DESACTIVEE.',
            'date_fin.after'  => 'La date de fin doit être postérieure à la date de début.',
        ];
    }

    /**
     * Confirmation explicite requise uniquement lorsque la cible bloque la plateforme.
     * (Ne peut pas être exprimé via required_if+accepted : "accepted" est une règle implicite
     * de Laravel — elle s'exécute même quand le champ est absent, y compris pour ACTIVE.)
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $statut = $this->input('statut');
            if (in_array($statut, ['MAINTENANCE', 'DESACTIVEE'], true) && !$this->boolean('confirmation')) {
                $validator->errors()->add(
                    'confirmation',
                    'Une confirmation explicite est requise pour passer en maintenance ou désactiver la plateforme.',
                );
            }
        });
    }
}
