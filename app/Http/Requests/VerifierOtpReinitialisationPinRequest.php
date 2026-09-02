<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifierOtpReinitialisationPinRequest extends FormRequest
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
            'code_otp' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'code_otp.required' => 'Le code de vérification est obligatoire.',
            'code_otp.digits'   => 'Le code de vérification doit contenir exactement 6 chiffres.',
        ];
    }
}
