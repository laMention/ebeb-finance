<?php

namespace App\Interfaces;

interface SmsProviderInterface
{
    /**
     * Envoie un SMS via le fournisseur.
     *
     * @param string $to       Numéro de destination
     * @param string $message  Contenu du SMS
     * @param array  $config   Configuration spécifique au fournisseur (api_url, api_key, sender_id, ...)
     * @return array{success: bool, message: string}
     */
    public function envoyer(string $to, string $message, array $config): array;
}
