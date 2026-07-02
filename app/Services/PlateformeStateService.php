<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\PlateformeEtat;
use App\Models\PlateformeHistorique;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlateformeStateService
{
    private const CACHE_KEY = 'plateforme:etat';
    private const CACHE_TTL = 30; // secondes

    /**
     * Retourne la ligne singleton brute (statut configuré), la crée si absente.
     */
    public function getEtatBrut(): PlateformeEtat
    {
        return PlateformeEtat::firstOrCreate([], ['statut' => 'ACTIVE']);
    }

    /**
     * Retourne le statut effectif à l'instant présent, en tenant compte
     * d'une éventuelle fenêtre planifiée (date_debut / date_fin).
     */
    public function getStatutEffectif(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->resoudreStatutEffectif($this->getEtatBrut());
        });
    }

    private function resoudreStatutEffectif(PlateformeEtat $etat): array
    {
        $maintenant = Carbon::now();
        $statutEffectif = $etat->statut;

        if ($statutEffectif !== 'ACTIVE') {
            // Fenêtre planifiée pas encore démarrée → toujours ACTIVE
            if ($etat->date_debut && $maintenant->lt($etat->date_debut)) {
                $statutEffectif = 'ACTIVE';
            }
            // Fenêtre expirée → retour automatique à ACTIVE
            elseif ($etat->date_fin && $maintenant->gt($etat->date_fin)) {
                $statutEffectif = 'ACTIVE';
            }
        }

        return [
            'statut'          => $statutEffectif,
            'statut_configure'=> $etat->statut,
            'message'         => $etat->message,
            'motif'           => $etat->motif,
            'date_debut'      => $etat->date_debut?->toIso8601String(),
            'date_fin'        => $etat->date_fin?->toIso8601String(),
            'modifie_par'     => $etat->modifie_par,
            'updated_at'      => $etat->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Bascule l'état global de la plateforme, historise le changement
     * et l'inscrit dans le journal d'audit général.
     */
    public function changerStatut(
        string $statut,
        ?string $message,
        ?string $motif,
        ?Carbon $dateDebut,
        ?Carbon $dateFin,
        Administrateur $admin,
        ?string $ip = null,
    ): PlateformeEtat {
        return DB::transaction(function () use ($statut, $message, $motif, $dateDebut, $dateFin, $admin, $ip) {
            $etat = $this->getEtatBrut();

            $avant = $etat->only(['statut', 'message', 'motif', 'date_debut', 'date_fin']);

            $etat->update([
                'statut'      => $statut,
                'message'     => $message,
                'motif'       => $motif,
                'date_debut'  => $dateDebut,
                'date_fin'    => $dateFin,
                'modifie_par' => $admin->id,
            ]);

            PlateformeHistorique::create([
                'statut_precedent' => $avant['statut'],
                'statut_nouveau'   => $statut,
                'message'          => $message,
                'motif'            => $motif,
                'date_debut'       => $dateDebut,
                'date_fin'         => $dateFin,
                'modifie_par'      => $admin->id,
                'ip_adresse'       => $ip,
            ]);

            AuditLogger::log(
                'PLATEFORME.STATUT_CHANGE',
                $admin,
                'plateforme',
                $etat->id,
                $avant,
                $etat->only(['statut', 'message', 'motif', 'date_debut', 'date_fin']),
                $ip,
            );

            Cache::forget(self::CACHE_KEY);

            return $etat->fresh();
        });
    }

    /**
     * Historique paginé des changements d'état.
     */
    public function historique(int $perPage = 20): LengthAwarePaginator
    {
        return PlateformeHistorique::with('modifiePar')
            ->latest()
            ->paginate($perPage);
    }
}
