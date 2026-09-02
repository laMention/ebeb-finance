<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'           => ['required', 'string', 'max:100'],
            'prenom'        => ['nullable', 'string', 'max:100'],
            'email'         => ['required', 'email', 'max:150', 'unique:administrateurs,email'],
            'password'      => ['required', 'string', 'min:12', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/'],
            'telephone'     => ['nullable', 'string', 'max:20', 'unique:administrateurs,telephone'],
            'ville'         => ['nullable', 'string', 'max:100'],
            'adresse'       => ['nullable', 'string', 'max:255'],
            'statut_compte' => ['sometimes', 'in:ACTIF,INACTIF'],
            'role_id'       => ['nullable', 'integer', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'     => 'Un administrateur avec cet email existe déjà.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'password.min'     => 'Le mot de passe doit contenir au moins 12 caractères.',
            'password.regex'   => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
        ];
    }
}
