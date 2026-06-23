<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id ?? $this->route('role');

        return [
            'display_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'description'  => ['sometimes', 'nullable', 'string', 'max:500'],
            'name'         => ['sometimes', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($roleId)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Un rôle avec ce code existe déjà.',
        ];
    }
}
