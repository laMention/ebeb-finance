<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifierCode2FARequest extends FormRequest
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
            'challenge_id' => 'required|string',
            'code'         => 'required|digits:6',
        ];
    }

    public function messages(): array
    {
        return [
            'challenge_id.required' => 'Session de vérification manquante. Veuillez vous reconnecter.',
            'code.required'         => 'Le code de vérification est obligatoire.',
            'code.digits'           => 'Le code de vérification doit contenir exactement 6 chiffres.',
        ];
    }
}
