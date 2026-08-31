<?php

namespace App\Services;

use App\Models\Operation;
use App\Models\TypeCotisation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RecapitulatifService
{
    private const TYPES_COTISATIONS = [
        'COTISATION_CNPS',
        'COTISATION_AMU',
        'COTISATION_PERSONNALISEE',
        'ASSURANCE_PERSONNALISEE',
        'PRELEVEMENT_COTISATION',
    ];

    private const TYPES_COMMISSIONS = [
        'COMMISSION_PLATEFORME',
        'COMMISSION',
    ];

    private const TYPES_EPARGNE = [
        'EPARGNE',
        'PRELEVEMENT_EPARGNE',
    ];

    /**
     * Calcule le récapitulatif des prélèvements et le solde disponible pour une période donnée.
     *
     * @param  User   $user
     * @param  array  $params  [mois, annee] ou [date_debut, date_fin]
     */
    public function recapitulatif(User $user, array $params): array
    {
        [$debut, $fin, $periode] = $this->resoudrePeriode($params);

        $operations = Operation::where('user_id', $user->id)
            ->where('statut', 'SUCCES')
            ->whereBetween('date_operation', [$debut, $fin])
            ->with('type_cotisation')
            ->get();

        $totalRecu       = $this->somme($operations, ['PAIEMENT_CLIENT', 'REVERSEMENT', 'REVERSEMENT_ESCROW']);
        $cotisations     = $this->ventilerCotisations($operations);
        $totalCotisations= $cotisations->sum('montant');
        $commissions     = $this->ventilerCommissions($operations);
        $totalCommissions= $commissions->sum('montant');
        $totalEpargne    = $this->somme($operations, self::TYPES_EPARGNE);

        $totalPrelevementsHorsEpargne = bcadd((string) $totalCotisations, (string) $totalCommissions, 2);
        $soldeTheorique  = bcsub((string) $totalRecu, $totalPrelevementsHorsEpargne, 2);
        $soldeDisponible = bcsub($soldeTheorique, (string) $totalEpargne, 2);
        $totalObjectifMensuel = $this->calculerObjectifMensuel($user);

        return [
            'periode'                         => $periode,
            'total_recu'                      => $this->formater($totalRecu),
            'cotisations'                     => $cotisations->values(),
            'total_cotisations'               => $this->formater($totalCotisations),
            'total_objectif_mensuel'          => $this->formater($totalObjectifMensuel),
            'commissions'                     => $commissions->values(),
            'total_commissions'               => $this->formater($totalCommissions),
            'total_prelevements_hors_epargne' => $this->formater($totalPrelevementsHorsEpargne),
            'solde_theorique'                 => $this->formater($soldeTheorique),
            'total_epargne'                   => $this->formater($totalEpargne),
            'solde_disponible'                => $this->formater($soldeDisponible),
        ];
    }

    /**
     * Objectif mensuel total, indépendant de la période affichée (c'est une
     * cible déclarée/configurée, pas un montant effectivement mouvementé) :
     *  - CNPS   : `declaration_revenus.montant_cotisation_mensuelle` déclaré
     *             par l'utilisateur à l'inscription ;
     *  - AMU et cotisations personnalisées : `type_cotisations.default_valeur`
     *    de chaque type actif (global ou propre à l'utilisateur).
     */
    private function calculerObjectifMensuel(User $user): float
    {
        $objectifCnps = (float) ($user->declarationRevenu?->montant_cotisation_mensuelle ?? 0);

        $autresTypes = TypeCotisation::where('est_actif', true)
            ->where('code', '!=', 'CNPS')
            ->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->get();

        $objectifAutres = (float) $autresTypes->sum(
            fn (TypeCotisation $type) => (float) ($type->default_valeur ?? 0)
        );

        return $objectifCnps + $objectifAutres;
    }

    /**
     * Soldes globaux (toute la durée de vie du compte, sans filtre de période) pour l'app mobile :
     *  - solde_principal : montant réellement disponible hors épargne
     *      (total reçu - cotisations - commissions - épargne prélevée)
     *  - solde_epargne   : total cumulé des opérations de type EPARGNE (indépendant du solde principal)
     */
    public function soldesGlobaux(User $user): array
    {
        $operations = Operation::where('user_id', $user->id)
            ->where('statut', 'SUCCES')
            ->get(['type_operation', 'montant']);

        $totalRecu        = $this->somme($operations, ['PAIEMENT_CLIENT', 'REVERSEMENT', 'REVERSEMENT_ESCROW']);
        $totalCotisations = $this->somme($operations, self::TYPES_COTISATIONS);
        $totalCommissions = $this->somme($operations, self::TYPES_COMMISSIONS);
        $totalEpargne     = $this->somme($operations, ['EPARGNE']);

        $soldePrincipal = bcsub(
            bcsub(bcsub((string) $totalRecu, (string) $totalCotisations, 2), (string) $totalCommissions, 2),
            (string) $totalEpargne,2 
        );

        return [
            'solde_principal' => $this->formater($soldePrincipal),
            'solde_epargne'   => $this->formater($totalEpargne),
        ];
    }

    // ─── Helpers privés ──────────────────────────────────────────────────────

    private function resoudrePeriode(array $params): array
    {
        if (!empty($params['date_debut']) && !empty($params['date_fin'])) {
            $debut = Carbon::parse($params['date_debut'])->startOfDay();
            $fin   = Carbon::parse($params['date_fin'])->endOfDay();

            return [
                $debut,
                $fin,
                [
                    'type'       => 'intervalle',
                    'date_debut' => $debut->toDateString(),
                    'date_fin'   => $fin->toDateString(),
                ],
            ];
        }

        $mois  = (int) ($params['mois']  ?? now()->month);
        $annee = (int) ($params['annee'] ?? now()->year);
        $debut = Carbon::create($annee, $mois, 1)->startOfMonth();
        $fin   = $debut->copy()->endOfMonth();

        return [
            $debut,
            $fin,
            [
                'type'       => 'mensuel',
                'mois'       => $mois,
                'annee'      => $annee,
                'libelle'    => ucfirst($debut->translatedFormat('F Y')),
                'date_debut' => $debut->toDateString(),
                'date_fin'   => $fin->toDateString(),
            ],
        ];
    }

    private function ventilerCotisations(Collection $operations): Collection
    {
        return $operations
            ->filter(fn ($op) => in_array($op->type_operation, self::TYPES_COTISATIONS))
            ->groupBy(fn ($op) => $op->type_cotisation_id ?? $op->type_operation)
            ->map(function (Collection $groupe) {
                $premier  = $groupe->first();
                $type     = $premier->type_cotisation;
                $montant  = $groupe->sum(fn ($op) => (float) $op->montant);

                return [
                    'type_operation'     => $premier->type_operation,
                    // Indispensable pour rattacher un montant versé à un type
                    // de cotisation précis : plusieurs cotisations
                    // personnalisées distinctes partagent le même
                    // `type_operation` (COTISATION_PERSONNALISEE), seul cet
                    // identifiant les distingue.
                    'type_cotisation_id' => $premier->type_cotisation_id,
                    'libelle'        => $type?->libelle ?? $this->libelleParDefaut($premier->type_operation),
                    'categorie'      => $type?->categorie ?? null,
                    'montant'        => $this->formater($montant),
                ];
            });
    }

    private function ventilerCommissions(Collection $operations): Collection
    {
        return $operations
            ->filter(fn ($op) => in_array($op->type_operation, self::TYPES_COMMISSIONS))
            ->groupBy('type_operation')
            ->map(function (Collection $groupe) {
                $premier = $groupe->first();
                $montant = $groupe->sum(fn ($op) => (float) $op->montant);

                return [
                    'type_operation' => $premier->type_operation,
                    'libelle'        => $premier->libelle ?? $this->libelleParDefaut($premier->type_operation),
                    'montant'        => $this->formater($montant),
                ];
            });
    }

    private function somme(Collection $operations, array $types): float
    {
        return $operations
            ->filter(fn ($op) => in_array($op->type_operation, $types))
            ->sum(fn ($op) => (float) $op->montant);
    }

    private function formater(float|string $montant): string
    {
        return number_format((float) $montant, 2, '.', '');
    }

    private function libelleParDefaut(string $typeOperation): string
    {
        return match ($typeOperation) {
            'COTISATION_CNPS'        => 'CNPS',
            'COTISATION_AMU'         => 'Assurance Maladie Universelle',
            'COTISATION_PERSONNALISEE'=> 'Cotisation personnalisée',
            'ASSURANCE_PERSONNALISEE' => 'Assurance personnalisée',
            'PRELEVEMENT_COTISATION'  => 'Prélèvement cotisation',
            'COMMISSION_PLATEFORME'   => 'Commission plateforme',
            'COMMISSION'              => 'Commission',
            default                   => $typeOperation,
        };
    }
}
