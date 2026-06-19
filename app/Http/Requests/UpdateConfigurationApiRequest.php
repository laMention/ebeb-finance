<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigurationApiRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'moyen_paiement_id' => ['sometimes', 'uuid', 'exists:moyen_paiements,id'],
            'url_api'           => ['sometimes', 'url', 'max:500'],
            'url_webhook'       => ['sometimes', 'url', 'max:500'],
            'identifiant_api'   => ['nullable', 'string', 'max:255'],
            'cle_api'           => ['nullable', 'string', 'max:1000'],
            'environnement'     => ['sometimes', 'in:SANDBOX,PRODUCTION'],
            'est_actif'         => ['sometimes', 'boolean'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'moyen_paiement_id.exists' => 'Le moyen de paiement sélectionné est invalide.',
            'url_api.url'              => "L'URL API doit être une URL valide.",
            'url_webhook.url'          => "L'URL Webhook doit être une URL valide.",
            'environnement.in'         => "L'environnement doit être SANDBOX ou PRODUCTION.",
        ];
    }
}
