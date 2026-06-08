<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConnexionRequest extends FormRequest
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
            'email_telephone' => 'required|string',
            'password' => 'required|min:8'
        ];
    }
    public function messages(): array
    {
        return [
            'email_telephone.required'=> 'Veuillez renseigner votre email ou numero de téléphone',
            'password.required'=> 'Mot de passe obligatoire pour vous connecter',
            'password.min'=> 'Le mot de passe doit contenir au moins :min caractères',
        ];
    }
}
