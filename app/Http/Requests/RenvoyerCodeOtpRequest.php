<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RenvoyerCodeOtpRequest extends FormRequest
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
            'telephone' => ['required', 'string', 'min:10', 'max:14', 'regex:/^[0-9+]+$/'],

        ];
    }

    public function messages(): array
    {
        return [
            'telephone.required' => "Le numéro de téléphone est obligatoire.",
            'telephone.min'   => "Le numéro de téléphone doit contenir au moins :min chiffres.",
            'telephone.max'   => "Le numéro de téléphone doit contenir au maximum :max chiffres.",
            'telephone.regex' => "Le format du numéro de téléphone est invalide.",
        ];
    }
}
