<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParametreGlobalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $parametreGlobalId = $this->route('parametreGlobal')?->id;

        return [
            'cle' => [
                'required',
                'string',
                'max:255',
                Rule::unique('parametre_globals', 'cle')->ignore($parametreGlobalId),
            ],
            'valeur' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return [
            'cle.required'    => 'La clé est obligatoire.',
            'cle.string'      => 'La clé doit être une chaîne de caractères.',
            'cle.max'         => 'La clé ne peut pas dépasser 255 caractères.',
            'cle.unique'      => 'Cette clé existe déjà.',
            'valeur.required' => 'La valeur est obligatoire.',
            'valeur.string'   => 'La valeur doit être une chaîne de caractères.',
            'valeur.max'      => 'La valeur ne peut pas dépasser 255 caractères.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'description.max'    => 'La description ne peut pas dépasser 1024 caractères.',
        ];
    }
}
