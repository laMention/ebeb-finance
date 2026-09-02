<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReinitialiserCodePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Pas de `ancien_code_pin` ici : l'identité a déjà été prouvée par OTP
     * (voir `UserService::reinitialiserCodePin()`), c'est tout l'intérêt du
     * parcours « code PIN oublié ».
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nouveau_code_pin'              => ['required', 'digits:6', 'confirmed'],
            'nouveau_code_pin_confirmation' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'nouveau_code_pin.required'              => 'Le nouveau code PIN est obligatoire.',
            'nouveau_code_pin.digits'                => 'Le nouveau code PIN doit contenir exactement 6 chiffres.',
            'nouveau_code_pin.confirmed'              => 'La confirmation du nouveau code PIN ne correspond pas.',
            'nouveau_code_pin_confirmation.required'  => 'La confirmation du nouveau code PIN est obligatoire.',
            'nouveau_code_pin_confirmation.digits'    => 'La confirmation du code PIN doit contenir exactement 6 chiffres.',
        ];
    }
}
