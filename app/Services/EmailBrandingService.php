<?php

namespace App\Services;

use App\Models\ParametreGeneral;

/**
 * Fournit les données de marque (logo, nom, slogan, etc.) à injecter
 * dans tous les templates d'email. Le résultat est mis en cache statiquement
 * pour éviter plusieurs requêtes DB par email.
 */
class EmailBrandingService
{
    private static ?array $cache = null;

    public static function get(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $p   = ParametreGeneral::first();
        $nom = $p?->nom_plateforme ?? config('app.name');

        self::$cache = [
            // logo_email prioritaire, sinon logo_principal, sinon null → affichage texte
            'logo_url'       => $p?->logo_email_url ?? $p?->logo_principal_url,
            'nom_plateforme' => $nom,
            'slogan'         => $p?->slogan,
            'site_web'       => $p?->site_web,
            'copyright'      => $p?->copyright ?? ('© ' . date('Y') . ' ' . $nom . '. Tous droits réservés.'),
        ];

        return self::$cache;
    }

    /** Vide le cache (utile en tests ou après mise à jour des paramètres). */
    public static function flush(): void
    {
        self::$cache = null;
    }
}
