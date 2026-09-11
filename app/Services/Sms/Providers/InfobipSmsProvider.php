<?php

namespace App\Services\Sms\Providers;

use App\Interfaces\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * Fournisseur Infobip — payload et authentification propres à cette API,
 * seule partie non pilotable depuis le panel admin (voir SmsProviderFactory).
 * URL, chemin d'API, clé et sender viennent tous de `$config`.
 */
class InfobipSmsProvider implements SmsProviderInterface
{
    public function envoyer(string $to, string $message, array $config): array
    {
        $baseUrl  = $config['api_url'] ?? null;
        $apiKey   = $config['api_key'] ?? null;
        $sender   = $config['sender_id'] ?? 'E-BEB';
        $endpoint = $config['endpoint'] ?? '/sms/2/text/advanced';

        if (!$baseUrl || !$apiKey) {
            return ['success' => false, 'message' => 'Configuration Infobip incomplète (api_url ou api_key manquant).'];
        }

        if (!$to) {
            return ['success' => false, 'message' => 'Numéro de destination manquant.'];
        }

        $endpointFinal = rtrim($baseUrl, '/') . $endpoint;

        try {
            $response = Http::withHeaders([
                    'Authorization' => "App {$apiKey}",
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])
                ->timeout(10)
                ->post($endpointFinal, [
                    'messages' => [
                        [
                            'from' => $sender,
                            'destinations' => [
                                ['to' => $to],
                            ],
                            'text' => $message,
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'SMS envoyé avec succès via Infobip.'];
            }

            $errorMsg = $response->json('requestError.serviceException.text')
                ?? $response->json('message')
                ?? $response->status();

            return ['success' => false, 'message' => 'Échec SMS (Infobip) : ' . $errorMsg];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur connexion Infobip : ' . $e->getMessage()];
        }
    }
}
