<?php

namespace App\Services;

use App\Models\Alerte;

class AlerteGenerator
{
    public static function kyc(string $niveau, string $titre, string $description, ?string $lien = null): void
    {
        self::creer('KYC', $niveau, $titre, $description, $lien);
    }

    public static function utilisateur(string $niveau, string $titre, string $description, ?string $lien = null): void
    {
        self::creer('UTILISATEUR', $niveau, $titre, $description, $lien);
    }

    public static function transaction(string $niveau, string $titre, string $description, ?string $lien = null): void
    {
        self::creer('TRANSACTION', $niveau, $titre, $description, $lien);
    }

    public static function reversement(string $niveau, string $titre, string $description, ?string $lien = null): void
    {
        self::creer('REVERSEMENT', $niveau, $titre, $description, $lien);
    }

    public static function systeme(string $niveau, string $titre, string $description, ?string $lien = null): void
    {
        self::creer('SYSTEME', $niveau, $titre, $description, $lien);
    }

    public static function creer(string $typeAlerte, string $niveau, string $titre, string $description, ?string $lien = null): void
    {
        try {
            Alerte::create([
                'type_alerte'               => strtoupper($typeAlerte),
                'titre'                     => $titre,
                'description'               => $description,
                'niveau'                    => strtoupper($niveau),
                'lien_vers_action_concerne' => $lien,
            ]);
        } catch (\Throwable) {
            // Ne jamais interrompre le flux principal pour une alerte échouée
        }
    }
}
