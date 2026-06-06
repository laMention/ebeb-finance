<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerificationOtpRequest extends FormRequest
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
            'telephone' => 'required|size:10|exists:users,telephone',
            'code_otp' => 'required|numeric|size:6'
            // |exists:session_otps,code_otp'
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.required' => "Le numéro de téléphone est obligatoire.",
            'telephone.size'     => "Le numéro de téléphone doit contenir exactement :size chiffres.",
            'telephone.exists'   => "Ce numéro de téléphone est invalide ou n\'existe pas.",

            'code_otp.required' => "Le code OTP est obligatoire pour vérifier le numéro de téléphone.",
            'code_otp.size'     => "Le code OTP doit contenir exactement :size chiffres.",
        ];
    }

}
