<?php

namespace App\Http\Requests\ControlPanel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangerEtatSurfaceRequest extends FormRequest
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
            'statut'  => 'required|string|in:ACTIF,DESACTIVE',
            'message' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'statut.required' => "Le statut de la surface est obligatoire.",
            'statut.in'       => 'Statut invalide. Valeurs autorisées : ACTIF, DESACTIVE.',
        ];
    }
}
