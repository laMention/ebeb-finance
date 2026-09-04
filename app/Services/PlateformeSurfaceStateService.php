<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\PlateformeSurfaceEtat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Contrôle indépendant de l'état ACTIF/DESACTIVE de chaque surface
 * (SITE_WEB, PANEL_ADMIN), séparément du kill switch global
 * (voir PlateformeStateService — reste la source de vérité pour l'API FRONT
 * et n'est jamais modifié par ce service).
 */
class PlateformeSurfaceStateService
{
    public const SURFACES = ['SITE_WEB', 'PANEL_ADMIN'];

    private const CACHE_PREFIX = 'plateforme:surface:';
    private const CACHE_TTL = 30; // secondes

    public function getEtatBrut(string $surface): PlateformeSurfaceEtat
    {
        return PlateformeSurfaceEtat::firstOrCreate(['surface' => $surface], ['statut' => 'ACTIF']);
    }

    public function getStatut(string $surface): array
    {
        return Cache::remember(self::CACHE_PREFIX . $surface, self::CACHE_TTL, function () use ($surface) {
            $etat = $this->getEtatBrut($surface);

            return [
                'surface'     => $etat->surface,
                'statut'      => $etat->statut,
                'message'     => $etat->message,
                'modifie_par' => $etat->modifie_par,
                'updated_at'  => $etat->updated_at?->toIso8601String(),
            ];
        });
    }

    public function getTousLesEtats(): array
    {
        return collect(self::SURFACES)
            ->mapWithKeys(fn (string $surface) => [$surface => $this->getStatut($surface)])
            ->all();
    }

    public function changerStatut(
        string $surface,
        string $statut,
        ?string $message,
        Administrateur $admin,
        ?string $ip = null,
    ): PlateformeSurfaceEtat {
        return DB::transaction(function () use ($surface, $statut, $message, $admin, $ip) {
            $etat = $this->getEtatBrut($surface);
            $avant = $etat->only(['statut', 'message']);

            $etat->update([
                'statut'      => $statut,
                'message'     => $message,
                'modifie_par' => $admin->id,
            ]);

            AuditLogger::log(
                'PLATEFORME.SURFACE_STATUT_CHANGE',
                $admin,
                'plateforme_surface',
                $etat->id,
                array_merge($avant, ['surface' => $surface]),
                array_merge($etat->only(['statut', 'message']), ['surface' => $surface]),
                $ip,
            );

            Cache::forget(self::CACHE_PREFIX . $surface);

            return $etat->fresh();
        });
    }
}
