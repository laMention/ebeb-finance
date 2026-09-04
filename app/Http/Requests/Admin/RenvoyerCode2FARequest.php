<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RenvoyerCode2FARequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'challenge_id.required' => 'Session de vérification manquante. Veuillez vous reconnecter.',
        ];
    }
}
