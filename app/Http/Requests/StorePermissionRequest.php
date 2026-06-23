<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:150'],
            'display_name' => ['nullable', 'string', 'max:200'],
            'module'       => ['nullable', 'string', 'max:100'],
            'description'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
