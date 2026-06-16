<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DefinirCodePinRequest extends FormRequest
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
            //
            'telephone' => 'required|min:10|max:14|exists:users,telephone',
            'password' => 'required|size:6',
        ];
    }
    public function messages(): array
    {
        return [
            'telephone.required' => "Le numéro de téléphone est obligatoire.",
            'telephone.min'     => "Le numéro de téléphone doit contenir au moins :min chiffres.",
            'telephone.max'     => "Le numéro de téléphone doit contenir au maiximum :max chiffres.",
            'telephone.exists'   => "Ce numéro de téléphone est invalide ou n\'existe pas.",

            'password.required' => "Le code OTP est obligatoire pour vérifier le numéro de téléphone.",
            'password.size'     => "Le code PIN doit contenir exactement :size chiffres.",
        ];
    }
}
