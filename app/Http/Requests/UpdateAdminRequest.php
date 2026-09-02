<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $adminId = $this->route('admin')?->id ?? $this->route('admin');

        return [
            'nom'           => ['sometimes', 'string', 'max:100'],
            'prenom'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'email'         => ['sometimes', 'email', 'max:150', Rule::unique('administrateurs', 'email')->ignore($adminId)],
            'password'      => ['nullable', 'string', 'min:12', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/'],
            'telephone'     => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('administrateurs', 'telephone')->ignore($adminId)],
            'ville'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'adresse'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'statut_compte' => ['sometimes', 'in:ACTIF,INACTIF'],
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
