<?php

namespace App\Services\Push;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;

/**
 * Construit un client Messaging Firebase à partir du compte de service stocké
 * dans NotificationConfig (canal PUSH) — jamais du fichier de credentials
 * statique par défaut du package, pour rester pilotable depuis le panel admin
 * (même principe que SmsProviderFactory pour les SMS).
 */
class PushMessagingFactory
{
    public function depuisConfiguration(array $conf): Messaging
    {
        $serviceAccountJson = $conf['service_account_json'] ?? null;

        if (!$serviceAccountJson) {
            throw new \RuntimeException('Configuration Push incomplète (service_account_json manquant).');
        }

        $factory = (new Factory())->withServiceAccount($serviceAccountJson);

        if (!empty($conf['project_id'])) {
            $factory = $factory->withProjectId($conf['project_id']);
        }

        return $factory->createMessaging();
    }
}
