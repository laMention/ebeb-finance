<?php

namespace App\Services;

use App\Models\Cotisation;
use App\Models\Operation;
use Illuminate\Pagination\LengthAwarePaginator;

class CotisationAdminService
{
    private const TYPES_COTISATION = [
        'COTISATION_CNPS', 'COTISATION_AMU', 'COTISATION_PERSONNALISEE', 'ASSURANCE_PERSONNALISEE',
    ];

    public function kpis(): array
    {
        $cnps           = (float) Operation::where('type_operation', 'COTISATION_CNPS')->where('statut', 'SUCCES')->sum('montant');
        $amu            = (float) Operation::where('type_operation', 'COTISATION_AMU')->where('statut', 'SUCCES')->sum('montant');
        $assurances     = (float) Operation::where('type_operation', 'ASSURANCE_PERSONNALISEE')->where('statut', 'SUCCES')->sum('montant');
        $personnalisees = (float) Operation::where('type_operation', 'COTISATION_PERSONNALISEE')->where('statut', 'SUCCES')->sum('montant');
        $total          = $cnps + $amu + $assurances + $personnalisees;

        $cotisantsActifs = Operation::whereIn('type_operation', self::TYPES_COTISATION)
            ->where('statut', 'SUCCES')
            ->distinct('user_id')
            ->count('user_id');

        $conformes    = Cotisation::whereIn('statut', ['A_JOUR', 'OBJECTIF_ATTEINT'])->count();
        $nonConformes = Cotisation::where('statut', 'NON_A_JOUR')->count();

        return compact('total', 'cnps', 'amu', 'assurances', 'personnalisees', 'cotisantsActifs', 'conformes', 'nonConformes');
    }

    public function evolutionMensuelle(int $annee): array
    {
        $rows = Operation::selectRaw('MONTH(date_operation) as mois, type_operation, SUM(montant) as total')
            ->whereIn('type_operation', self::TYPES_COTISATION)
            ->where('statut', 'SUCCES')
            ->whereYear('date_operation', $annee)
            ->groupByRaw('MONTH(date_operation), type_operation')
            ->orderByRaw('MONTH(date_operation)')
            ->get()
            ->groupBy('mois');

        return $rows->map(fn($group, $mois) => [
            'mois'           => (int) $mois,
            'cnps'           => (float) ($group->firstWhere('type_operation', 'COTISATION_CNPS')?->total           ?? 0),
            'amu'            => (float) ($group->firstWhere('type_operation', 'COTISATION_AMU')?->total            ?? 0),
            'assurances'     => (float) ($group->firstWhere('type_operation', 'ASSURANCE_PERSONNALISEE')?->total   ?? 0),
            'personnalisees' => (float) ($group->firstWhere('type_operation', 'COTISATION_PERSONNALISEE')?->total  ?? 0),
        ])->values()->toArray();
    }

    public function parType(): array
    {
        return Operation::selectRaw('type_operation, COUNT(*) as nb_operations, COUNT(DISTINCT user_id) as nb_utilisateurs, SUM(montant) as total')
            ->whereIn('type_operation', self::TYPES_COTISATION)
            ->where('statut', 'SUCCES')
            ->groupBy('type_operation')
            ->get()
            ->map(fn($row) => [
                'type_operation'  => $row->type_operation,
                'nb_operations'   => (int) $row->nb_operations,
                'nb_utilisateurs' => (int) $row->nb_utilisateurs,
                'total'           => (float) $row->total,
            ])
            ->toArray();
    }

    public function lister(array $params): LengthAwarePaginator
    {
        $query = Cotisation::with([
            'user:id,nom,prenom,telephone,reference',
            'typeCotisation:id,libelle,code,categorie',
        ])->orderByDesc('annee')->orderByDesc('mois')->orderByDesc('created_at');

        if (!empty($params['recherche'])) {
            $r = $params['recherche'];
            $query->whereHas('user', fn($u) => $u
                ->where('nom', 'like', "%{$r}%")
                ->orWhere('prenom', 'like', "%{$r}%")
                ->orWhere('telephone', 'like', "%{$r}%")
                ->orWhere('reference', 'like', "%{$r}%")
            );
        }

        if (!empty($params['statut'])) {
            $query->where('statut', $params['statut']);
        }

        if (!empty($params['annee'])) {
            $query->where('annee', (int) $params['annee']);
        }

        if (!empty($params['mois'])) {
            $query->where('mois', (int) $params['mois']);
        }

        $perPage = isset($params['per_page']) ? min((int) $params['per_page'], 100) : 25;
        $page    = isset($params['page'])     ? (int) $params['page'] : 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
