<?php

namespace App\Services\Sms;

use App\Interfaces\SmsProviderInterface;
use App\Services\Sms\Providers\GenericHttpSmsProvider;
use App\Services\Sms\Providers\InfobipSmsProvider;

/**
 * Choisit l'implémentation `SmsProviderInterface` selon `fournisseur`
 * (`NotificationConfig.fournisseur`, piloté depuis le panel admin) — jamais
 * de fournisseur codé en dur dans les services appelants
 * (`NotificationConfigService`, `NotificationService`).
 *
 * Ajouter un fournisseur : créer une classe dans `App\Services\Sms\Providers`
 * implémentant `SmsProviderInterface` (voir `GenericHttpSmsProvider` comme
 * gabarit), puis l'ajouter à MAP ci-dessous.
 */
class SmsProviderFactory
{
    private const MAP = [
        'INFOBIP'   => InfobipSmsProvider::class,
        'GENERIQUE' => GenericHttpSmsProvider::class,
    ];

    /**
     * @param string|null $fournisseur Valeur de `NotificationConfig.fournisseur`.
     *        Un fournisseur vide ou non reconnu retombe sur le provider
     *        générique plutôt que d'échouer — une configuration incomplète
     *        (URL/clé manquantes) est déjà signalée par le provider
     *        lui-même, pas de raison de bloquer la résolution en amont.
     */
    public function resoudre(?string $fournisseur): SmsProviderInterface
    {
        $classe = self::MAP[strtoupper($fournisseur ?? '')] ?? GenericHttpSmsProvider::class;

        return app($classe);
    }
}
