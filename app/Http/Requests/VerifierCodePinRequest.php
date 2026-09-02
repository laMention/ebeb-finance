<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifierCodePinRequest extends FormRequest
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
            'code_pin' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'code_pin.required' => 'Le code PIN est obligatoire.',
            'code_pin.digits'   => 'Le code PIN doit contenir exactement 6 chiffres.',
        ];
    }
}
