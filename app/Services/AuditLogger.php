<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\LogAudit;

class AuditLogger
{
    public static function log(
        string        $action,
        ?Administrateur $admin = null,
        ?string       $entiteCible = null,
        ?string       $entiteId = null,
        array|null    $avant = null,
        array|null    $apres = null,
        ?string       $ip = null,
    ): void {
        try {
            LogAudit::create([
                'utilisateur'   => $admin
                    ? trim("{$admin->prenom} {$admin->nom}") . " <{$admin->email}>"
                    : null,
                'action'        => strtoupper($action),
                'entite_cible'  => $entiteCible,
                'entite_id'     => $entiteId,
                'donnees_avant' => $avant,
                'donnees_apres' => $apres,
                'ip_adresse'    => $ip ?? (request()->ip() ?? null),
            ]);
        } catch (\Throwable) {
            // Ne jamais interrompre le flux principal pour un log raté
        }
    }
}
