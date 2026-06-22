<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $permissionId = $this->route('permission')?->id ?? $this->route('permission');

        return [
            'name'         => ['sometimes', 'string', 'max:150', Rule::unique('permissions', 'name')->ignore($permissionId)],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:200'],
            'module'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'description'  => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
