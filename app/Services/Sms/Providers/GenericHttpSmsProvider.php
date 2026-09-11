<?php

namespace App\Services\Sms\Providers;

use App\Interfaces\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * Fournisseur générique pour toute API SMS REST simple (POST JSON, header
 * `Authorization: Bearer`). Sert de second exemple du pattern Strategy et de
 * gabarit à dupliquer pour un futur fournisseur non encore supporté — seule
 * la structure du payload/l'authentification changent d'un fournisseur à
 * l'autre, jamais l'URL/le chemin/la clé (pilotés depuis le panel admin).
 */
class GenericHttpSmsProvider implements SmsProviderInterface
{
    public function envoyer(string $to, string $message, array $config): array
    {
        $baseUrl  = $config['api_url'] ?? null;
        $apiKey   = $config['api_key'] ?? null;
        $sender   = $config['sender_id'] ?? 'E-BEB';
        $endpoint = $config['endpoint'] ?? '';

        if (!$baseUrl || !$apiKey) {
            return ['success' => false, 'message' => 'Configuration SMS incomplète (api_url ou api_key manquant).'];
        }

        if (!$to) {
            return ['success' => false, 'message' => 'Numéro de destination manquant.'];
        }

        $endpointFinal = rtrim($baseUrl, '/') . $endpoint;

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(10)
                ->post($endpointFinal, [
                    'to'      => $to,
                    'from'    => $sender,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'SMS envoyé avec succès.'];
            }

            $errorMsg = $response->json('message') ?? $response->status();

            return ['success' => false, 'message' => 'Échec SMS : ' . $errorMsg];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur connexion SMS : ' . $e->getMessage()];
        }
    }
}
