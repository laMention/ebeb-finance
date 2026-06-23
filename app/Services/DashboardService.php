<?php

namespace App\Services;

use App\Models\Alerte;
use App\Models\DocumentKYC;
use App\Models\Operation;
use App\Models\PaiementEntrant;
use App\Models\Reversement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    // -------------------------------------------------------------------------
    // Point d'entrée
    // -------------------------------------------------------------------------

    public static function resume(array $params): array
    {
        [$debut, $fin]         = self::parseRange($params);
        [$debutPrev, $finPrev] = self::prevRange($debut, $fin);

        return [
            'success'  => true,
            'periode'  => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
            'kpis'     => self::kpis($debut, $fin, $debutPrev, $finPrev),
            'charts'   => self::charts($debut, $fin),
            'alertes'  => self::alertes(),
            'transactions_recentes' => self::transactionsRecentes(),
            'nouveaux_utilisateurs' => self::nouveauxUtilisateurs(),
        ];
    }

    // -------------------------------------------------------------------------
    // Parsing de la période
    // -------------------------------------------------------------------------

    private static function parseRange(array $params): array
    {
        $periode = $params['periode'] ?? 'mois';

        if ($periode === 'personnalise') {
            $debut = Carbon::parse($params['date_debut'] ?? now()->startOfMonth())->startOfDay();
            $fin   = Carbon::parse($params['date_fin']   ?? now())->endOfDay();
            return [$debut, $fin];
        }

        $now = Carbon::now();
        return match ($periode) {
            'jour'    => [$now->copy()->startOfDay(),  $now->copy()->endOfDay()],
            'semaine' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'annee'   => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            default   => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()], // mois
        };
    }

    private static function prevRange(Carbon $debut, Carbon $fin): array
    {
        $duree = $debut->diffInDays($fin) + 1;
        $finPrev   = $debut->copy()->subDay()->endOfDay();
        $debutPrev = $finPrev->copy()->subDays($duree - 1)->startOfDay();
        return [$debutPrev, $finPrev];
    }

    // -------------------------------------------------------------------------
    // KPIs
    // -------------------------------------------------------------------------

    private static function kpis(Carbon $debut, Carbon $fin, Carbon $debutPrev, Carbon $finPrev): array
    {
        // ---- Utilisateurs ----
        $totalUsers     = User::count();
        $actifs         = User::where('statut', 'ACTIF')->count();
        $suspendus      = User::where('statut', 'SUSPENDU')->count();
        $newCurrent     = User::whereBetween('created_at', [$debut, $fin])->count();
        $newPrev        = User::whereBetween('created_at', [$debutPrev, $finPrev])->count();
        $evolUsers      = self::evolutionPct($newPrev, $newCurrent);

        // ---- KYC ----
        $kycEnAttente = DocumentKYC::where('statut', 'EN_ATTENTE')->count();

        // ---- Paiements / Volume ----
        $volCurrent = PaiementEntrant::where('statut', 'SUCCES')
            ->whereBetween('created_at', [$debut, $fin])
            ->sum('montant_brut');
        $volPrev = PaiementEntrant::where('statut', 'SUCCES')
            ->whereBetween('created_at', [$debutPrev, $finPrev])
            ->sum('montant_brut');
        $volTotal    = PaiementEntrant::where('statut', 'SUCCES')->sum('montant_brut');
        $evolVol     = self::evolutionPct($volPrev, $volCurrent);

        // ---- Taux de succès ----
        $pTotal   = PaiementEntrant::whereBetween('created_at', [$debut, $fin])->count();
        $pSucces  = PaiementEntrant::where('statut', 'SUCCES')->whereBetween('created_at', [$debut, $fin])->count();
        $pTotalP  = PaiementEntrant::whereBetween('created_at', [$debutPrev, $finPrev])->count();
        $pSuccesP = PaiementEntrant::where('statut', 'SUCCES')->whereBetween('created_at', [$debutPrev, $finPrev])->count();
        $tauxCurrent = $pTotal  > 0 ? round($pSucces  / $pTotal  * 100, 1) : 0.0;
        $tauxPrev    = $pTotalP > 0 ? round($pSuccesP / $pTotalP * 100, 1) : 0.0;
        $tauxEvol    = round($tauxCurrent - $tauxPrev, 1);

        // ---- Financier (all-time) ----
        $cnps        = Operation::where('type_operation', 'COTISATION_CNPS')->sum('montant');
        $amu         = Operation::where('type_operation', 'COTISATION_AMU')->sum('montant');
        $epargne     = Operation::whereIn('type_operation', ['EPARGNE', 'PRELEVEMENT_EPARGNE'])->sum('montant');
        $commissions = Operation::whereIn('type_operation', ['COMMISSION_PLATEFORME', 'COMMISSION'])->sum('montant');
        $reversements = Reversement::where('statut', 'EXECUTE')->sum('montant_total');

        return [
            'utilisateurs' => [
                'total'       => $totalUsers,
                'actifs'      => $actifs,
                'suspendus'   => $suspendus,
                'ce_mois'     => $newCurrent,
                'prev'        => $newPrev,
                'evolution_pct' => $evolUsers,
            ],
            'actifs' => [
                'total'         => $actifs,
                'evolution_pct' => self::evolutionPct(
                    User::where('statut', 'ACTIF')->whereBetween('updated_at', [$debutPrev, $finPrev])->count(),
                    User::where('statut', 'ACTIF')->whereBetween('updated_at', [$debut, $fin])->count()
                ),
            ],
            'suspendus' => [
                'total'         => $suspendus,
                'evolution_pct' => self::evolutionPct(
                    User::where('statut', 'SUSPENDU')->whereBetween('updated_at', [$debutPrev, $finPrev])->count(),
                    User::where('statut', 'SUSPENDU')->whereBetween('updated_at', [$debut, $fin])->count()
                ),
            ],
            'kyc_en_attente'   => $kycEnAttente,
            'volume_encaisse'  => [
                'total'         => round($volTotal),
                'ce_mois'       => round($volCurrent),
                'prev'          => round($volPrev),
                'evolution_pct' => $evolVol,
            ],
            'taux_succes' => [
                'global'       => $tauxCurrent,
                'prev'         => $tauxPrev,
                'evolution_pts' => $tauxEvol,
            ],
            'cnps'        => round($cnps),
            'amu'         => round($amu),
            'epargne'     => round($epargne),
            'commissions' => round($commissions),
            'reversements'=> round($reversements),
        ];
    }

    // -------------------------------------------------------------------------
    // Graphiques
    // -------------------------------------------------------------------------

    private static function charts(Carbon $debut, Carbon $fin): array
    {
        return [
            'paiements_14j'    => self::paiements14j(),
            'repartition'      => self::repartitionPrelevements($debut, $fin),
            'operateurs'       => self::repartitionOperateurs($debut, $fin),
            'inscriptions_12m' => self::inscriptions12m(),
        ];
    }

    private static function paiements14j(): array
    {
        $rows = PaiementEntrant::select(
                DB::raw('DATE(created_at) as jour'),
                DB::raw('SUM(montant_brut) as volume'),
                DB::raw('COUNT(*) as nb')
            )
            ->where('statut', 'SUCCES')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('jour')
            ->orderBy('jour')
            ->get()
            ->keyBy('jour');

        $result = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $row  = $rows[$date] ?? null;
            $result[] = [
                'day'    => 'J-' . $i,
                'date'   => $date,
                'volume' => $row ? (int) $row->volume : 0,
                'count'  => $row ? (int) $row->nb     : 0,
            ];
        }
        return $result;
    }

    private static function repartitionPrelevements(Carbon $debut, Carbon $fin): array
    {
        $types = [
            'EPARGNE'                 => 'Épargne',
            'PRELEVEMENT_EPARGNE'     => 'Épargne',
            'COTISATION_CNPS'         => 'CNPS',
            'COTISATION_AMU'          => 'AMU',
            'COTISATION_PERSONNALISEE'=> 'Assurances',
            'ASSURANCE_PERSONNALISEE' => 'Assurances',
            'COMMISSION_PLATEFORME'   => 'Commission',
            'COMMISSION'              => 'Commission',
        ];

        $rows = Operation::select('type_operation', DB::raw('SUM(montant) as total'))
            ->whereBetween('created_at', [$debut, $fin])
            ->whereIn('type_operation', array_keys($types))
            ->groupBy('type_operation')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $label = $types[$row->type_operation] ?? $row->type_operation;
            $grouped[$label] = ($grouped[$label] ?? 0) + (float) $row->total;
        }

        // Ajouter Reversé (opérations REVERSEMENT)
        $revTotal = Operation::whereIn('type_operation', ['REVERSEMENT', 'REVERSEMENT_ESCROW'])
            ->whereBetween('created_at', [$debut, $fin])
            ->sum('montant');
        if ($revTotal > 0) {
            $grouped['Reversé'] = (float) $revTotal;
        }

        $grandTotal = array_sum($grouped);
        if ($grandTotal == 0) {
            return [];
        }

        $colors = [
            'Épargne'    => 'var(--color-brand)',
            'CNPS'       => 'var(--color-violet)',
            'AMU'        => 'var(--color-chart-4)',
            'Assurances' => 'var(--color-chart-5)',
            'Commission' => 'var(--color-destructive)',
            'Reversé'    => 'var(--color-success)',
        ];

        $result = [];
        foreach ($grouped as $name => $total) {
            $result[] = [
                'name'  => $name,
                'value' => round($total / $grandTotal * 100, 1),
                'montant' => round($total),
                'color' => $colors[$name] ?? 'var(--color-muted-foreground)',
            ];
        }
        usort($result, fn ($a, $b) => $b['value'] <=> $a['value']);
        return $result;
    }

    private static function repartitionOperateurs(Carbon $debut, Carbon $fin): array
    {
        $colors = [
            'WAVE'         => '#1DC8F0',
            'ORANGE_MONEY' => '#FF7900',
            'ORANGE'       => '#FF7900',
            'MTN'          => '#FFCC00',
            'MTN_MONEY'    => '#FFCC00',
            'MOOV'         => '#0066B3',
            'MOOV_MONEY'   => '#0066B3',
        ];

        $labels = [
            'WAVE'         => 'Wave',
            'ORANGE_MONEY' => 'Orange Money',
            'ORANGE'       => 'Orange Money',
            'MTN'          => 'MTN',
            'MTN_MONEY'    => 'MTN',
            'MOOV'         => 'Moov',
            'MOOV_MONEY'   => 'Moov',
        ];

        $rows = PaiementEntrant::select(
                DB::raw('UPPER(operateur_source) as operateur'),
                DB::raw('SUM(montant_brut) as volume'),
                DB::raw('COUNT(*) as nb')
            )
            ->where('statut', 'SUCCES')
            ->whereBetween('created_at', [$debut, $fin])
            ->whereNotNull('operateur_source')
            ->groupBy('operateur')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $op    = strtoupper($row->operateur);
            $label = $labels[$op] ?? $op;
            if (!isset($grouped[$label])) {
                $grouped[$label] = ['volume' => 0, 'nb' => 0, 'color' => $colors[$op] ?? '#94A3B8'];
            }
            $grouped[$label]['volume'] += (float) $row->volume;
            $grouped[$label]['nb']     += (int) $row->nb;
        }

        $grandTotal = array_sum(array_column($grouped, 'volume'));

        $result = [];
        foreach ($grouped as $name => $data) {
            $result[] = [
                'name'   => $name,
                'volume' => round($data['volume']),
                'count'  => $data['nb'],
                'pct'    => $grandTotal > 0 ? round($data['volume'] / $grandTotal * 100, 1) : 0,
                'color'  => $data['color'],
            ];
        }
        usort($result, fn ($a, $b) => $b['volume'] <=> $a['volume']);
        return $result;
    }

    private static function inscriptions12m(): array
    {
        $rows = User::select(
                DB::raw('YEAR(created_at) as annee'),
                DB::raw('MONTH(created_at) as mois_num'),
                DB::raw('COUNT(*) as inscriptions')
            )
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('annee', 'mois_num')
            ->orderBy('annee')
            ->orderBy('mois_num')
            ->get()
            ->keyBy(fn ($r) => $r->annee . '-' . str_pad($r->mois_num, 2, '0', STR_PAD_LEFT));

        $moisLabels = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
        $result = [];

        for ($i = 11; $i >= 0; $i--) {
            $d   = now()->subMonths($i);
            $key = $d->format('Y') . '-' . $d->format('m');
            $row = $rows[$key] ?? null;
            $result[] = [
                'month'        => $moisLabels[(int) $d->format('m') - 1],
                'inscriptions' => $row ? (int) $row->inscriptions : 0,
            ];
        }
        return $result;
    }

    // -------------------------------------------------------------------------
    // Widgets
    // -------------------------------------------------------------------------

    private static function alertes(): array
    {
        return Alerte::whereIn('niveau', ['CRITIQUE', 'CRITICAL', 'ALERT', 'EMERGENCY'])
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get(['id', 'titre', 'description', 'niveau', 'created_at'])
            ->toArray();
    }

    private static function transactionsRecentes(): array
    {
        return PaiementEntrant::with('user:id,nom,prenom,profession')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get(['id', 'reference_externe', 'user_id', 'montant_brut', 'statut', 'operateur_source', 'created_at'])
            ->toArray();
    }

    private static function nouveauxUtilisateurs(): array
    {
        return User::orderBy('created_at', 'desc')
            ->limit(6)
            ->get(['id', 'nom', 'prenom', 'profession', 'telephone', 'statut', 'created_at'])
            ->map(fn ($u) => [
                'id'         => $u->id,
                'nom'        => trim("{$u->prenom} {$u->nom}"),
                'profession' => $u->profession,
                'telephone'  => $u->telephone,
                'statut'     => $u->statut,
                'created_at' => $u->created_at,
            ])
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private static function evolutionPct(float $prev, float $current): float
    {
        if ($prev == 0) return $current > 0 ? 100.0 : 0.0;
        return round(($current - $prev) / $prev * 100, 1);
    }
}
