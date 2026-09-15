<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'      => ['required', 'string', 'max:255'],
            'plateforme' => ['required', 'string', 'in:ANDROID,IOS'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'      => 'Le jeton de l\'appareil est obligatoire.',
            'token.string'        => 'Le jeton de l\'appareil doit être une chaîne de caractères.',
            'token.max'           => 'Le jeton de l\'appareil ne peut pas dépasser 255 caractères.',

            'plateforme.required' => 'La plateforme est obligatoire.',
            'plateforme.in'       => 'La plateforme doit être ANDROID ou IOS.',
        ];
    }
}
