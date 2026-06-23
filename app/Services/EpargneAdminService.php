<?php

namespace App\Services;

use App\Models\ObjectifEpargne;
use Illuminate\Pagination\LengthAwarePaginator;

class EpargneAdminService
{
    public function kpis(): array
    {
        $totalEpargne       = (float) ObjectifEpargne::sum('montant_epargne');
        $objectifsActifs    = ObjectifEpargne::where('est_actif', true)->count();
        $utilisateursActifs = ObjectifEpargne::where('est_actif', true)->distinct('user_id')->count('user_id');
        $tauxMoyen          = (float) (ObjectifEpargne::where('montant_cible', '>', 0)
            ->selectRaw('AVG(montant_epargne / montant_cible * 100) as taux')
            ->value('taux') ?? 0);

        $topEpargnants = ObjectifEpargne::with('user:id,nom,prenom,reference,telephone')
            ->orderByDesc('montant_epargne')
            ->limit(10)
            ->get()
            ->map(fn($o) => [
                'libelle'         => $o->libelle,
                'montant_epargne' => (float) $o->montant_epargne,
                'user' => $o->user ? [
                    'nom'       => $o->user->nom,
                    'prenom'    => $o->user->prenom,
                    'reference' => $o->user->reference,
                    'telephone' => $o->user->telephone,
                ] : null,
            ]);

        return [
            'total_epargne'       => $totalEpargne,
            'objectifs_actifs'    => $objectifsActifs,
            'utilisateurs_actifs' => $utilisateursActifs,
            'taux_moyen'          => round($tauxMoyen, 1),
            'top_epargnants'      => $topEpargnants,
        ];
    }

    public function lister(array $params): LengthAwarePaginator
    {
        $query = ObjectifEpargne::with(['user:id,nom,prenom,telephone,reference'])
            ->withCount('operations')
            ->orderByDesc('montant_epargne')
            ->orderByDesc('created_at');

        if (!empty($params['recherche'])) {
            $r = $params['recherche'];
            $query->where(function ($q) use ($r) {
                $q->where('libelle', 'like', "%{$r}%")
                  ->orWhereHas('user', fn($u) => $u
                      ->where('nom', 'like', "%{$r}%")
                      ->orWhere('prenom', 'like', "%{$r}%")
                      ->orWhere('telephone', 'like', "%{$r}%")
                      ->orWhere('reference', 'like', "%{$r}%")
                  );
            });
        }

        if (!empty($params['statut'])) {
            $today = now()->toDateString();
            if ($params['statut'] === 'OBJECTIF_DEPASSE') {
                $query->whereRaw('montant_epargne > montant_cible');
            } elseif ($params['statut'] === 'OBJECTIF_ATTEINT') {
                $query->whereRaw('montant_epargne >= montant_cible');
            } elseif ($params['statut'] === 'EN_RETARD') {
                $query->whereRaw('montant_epargne < montant_cible')
                      ->whereNotNull('date_limite')
                      ->whereDate('date_limite', '<', $today);
            } elseif ($params['statut'] === 'EN_COURS') {
                $query->whereRaw('montant_epargne < montant_cible')
                      ->where(fn($q) => $q->whereNull('date_limite')->orWhereDate('date_limite', '>=', $today));
            }
        }

        if (!empty($params['progression'])) {
            if ($params['progression'] === '0-25') {
                $query->whereRaw('montant_cible > 0 AND (montant_epargne / montant_cible * 100) BETWEEN 0 AND 25');
            } elseif ($params['progression'] === '26-50') {
                $query->whereRaw('montant_cible > 0 AND (montant_epargne / montant_cible * 100) BETWEEN 26 AND 50');
            } elseif ($params['progression'] === '51-75') {
                $query->whereRaw('montant_cible > 0 AND (montant_epargne / montant_cible * 100) BETWEEN 51 AND 75');
            } elseif ($params['progression'] === '76-99') {
                $query->whereRaw('montant_cible > 0 AND (montant_epargne / montant_cible * 100) BETWEEN 76 AND 99');
            } elseif ($params['progression'] === '100+') {
                $query->whereRaw('montant_epargne >= montant_cible AND montant_cible > 0');
            }
        }

        if (!empty($params['date_debut'])) {
            $query->whereDate('created_at', '>=', $params['date_debut']);
        }
        if (!empty($params['date_fin'])) {
            $query->whereDate('created_at', '<=', $params['date_fin']);
        }

        $perPage = isset($params['per_page']) ? min((int) $params['per_page'], 100) : 18;
        $page    = isset($params['page'])     ? (int) $params['page'] : 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function obtenirDetail(ObjectifEpargne $objectif): ObjectifEpargne
    {
        return $objectif->load([
            'user:id,nom,prenom,telephone,reference,email',
            'operations' => fn($q) => $q
                ->with('paiement_entrant:id,operateur_source,reference_externe')
                ->orderByDesc('date_operation')
                ->limit(50),
        ]);
    }
}
