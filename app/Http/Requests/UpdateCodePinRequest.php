<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCodePinRequest extends FormRequest
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
            'ancien_code_pin'              => ['required', 'digits:6'],
            'nouveau_code_pin'             => ['required', 'digits:6', 'confirmed'],
            'nouveau_code_pin_confirmation'=> ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'ancien_code_pin.required'              => "L'ancien code PIN est obligatoire.",
            'ancien_code_pin.digits'                => "L'ancien code PIN doit contenir exactement 6 chiffres.",
            'nouveau_code_pin.required'             => "Le nouveau code PIN est obligatoire.",
            'nouveau_code_pin.digits'               => "Le nouveau code PIN doit contenir exactement 6 chiffres.",
            'nouveau_code_pin.confirmed'            => "La confirmation du nouveau code PIN ne correspond pas.",
            'nouveau_code_pin_confirmation.required'=> "La confirmation du nouveau code PIN est obligatoire.",
            'nouveau_code_pin_confirmation.digits'  => "La confirmation du code PIN doit contenir exactement 6 chiffres.",
        ];
    }
}
