<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConfigurationApiRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'moyen_paiement_id' => ['required', 'uuid', 'exists:moyen_paiements,id'],
            'url_api'           => ['required', 'url', 'max:500'],
            'url_webhook'       => ['required', 'url', 'max:500'],
            'identifiant_api'   => ['nullable', 'string', 'max:255'],
            'cle_api'           => ['nullable', 'string', 'max:1000'],
            'environnement'     => ['required', 'in:SANDBOX,PRODUCTION'],
            'est_actif'         => ['boolean'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'moyen_paiement_id.required' => 'Le moyen de paiement est obligatoire.',
            'moyen_paiement_id.exists'   => 'Le moyen de paiement sélectionné est invalide.',
            'url_api.required'           => "L'URL API est obligatoire.",
            'url_api.url'                => "L'URL API doit être une URL valide.",
            'url_webhook.required'       => "L'URL Webhook est obligatoire.",
            'url_webhook.url'            => "L'URL Webhook doit être une URL valide.",
            'environnement.required'     => "L'environnement est obligatoire.",
            'environnement.in'           => "L'environnement doit être SANDBOX ou PRODUCTION.",
        ];
    }
}
