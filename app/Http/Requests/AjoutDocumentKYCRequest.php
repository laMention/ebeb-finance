<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AjoutDocumentKYCRequest extends FormRequest
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
            // DOCUMENTS KYC
            'type_document'=> ['required', 'string'],
            'numero_document'=> ['required',  'string'],
            'document_etablie_le'=> ['required', 'date'],
            'document_expire_le'=> [ 'required', 'date'],
            'url_recto' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'url_verso' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'url_selfie' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            // 'url_selfie'=> ['nullable', 'file','image','mimes:jpeg,png,jpg,gif,svg|max:2048'],
        ];
    }

    public function messages(): array
    {
        return [            
            'type_document.required' => 'Le type de document est obligatoire.',
            'type_document.string' => 'Le type de document doit être une chaîne de caractères.',

            'numero_document.required' => 'Le numéro de document est obligatoire.',
            'numero_document.string' => 'Le numéro de document doit être une chaîne de caractères.',

            'document_etablie_le.required' => 'La date d’émission du document est obligatoire.',
            'document_etablie_le.date' => 'La date d’émission du document doit être une date valide.',

            'document_expire_le.required' => 'La date d’expiration du document est obligatoire.',
            'document_expire_le.date' => 'La date d’expiration du document doit être une date valide.',

            'url_recto.required' => 'L’URL du recto est obligatoire.',
            'url_recto.string' => 'L’URL du recto doit être une chaîne de caractères.',

            'url_verso.required' => 'L’URL du verso est obligatoire.',
            'url_verso.string' => 'L’URL du verso doit être une chaîne de caractères.',

            'url_selfie.string' => 'L’URL du selfie doit être une chaîne de caractères.',
        ];
    }
}
