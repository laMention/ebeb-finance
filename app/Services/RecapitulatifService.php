<?php

namespace App\Services;

use App\Models\Operation;
use App\Models\ReglePrelevement;
use App\Models\Remboursement;
use App\Models\TypeCotisation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RecapitulatifService
{
    // Argent reçu de l'extérieur (paiements clients, reversements) — utilisé
    // par `soldesGlobaux()`, qui n'effectue aucun filtrage par cotisation
    // configurée : y ajouter les remboursements (voir TYPES_RECU_GLOBAL) est
    // le seul mécanisme disponible pour restituer un montant dans ce calcul.
    private const TYPES_RECU = [
        'PAIEMENT_CLIENT',
        'REVERSEMENT',
        'REVERSEMENT_ESCROW',
    ];

    // `soldesGlobaux()` uniquement : un remboursement restitue de l'argent
    // exactement comme un paiement, sans altérer l'opération originale
    // (conservée pour traçabilité). `recapitulatif()`, lui, nette chaque
    // remboursement directement contre la cotisation d'origine (voir
    // `ventilerCotisations()`) — y ajouter aussi le remboursement ici
    // compterait le même montant deux fois.
    private const TYPES_RECU_GLOBAL = [...self::TYPES_RECU, 'REMBOURSEMENT_COTISATION'];

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

        $totalRecu       = $this->somme($operations, self::TYPES_RECU);
        $cotisations     = $this->ventilerCotisations($operations, $user->id);
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
     *  - CNPS : `declaration_revenus.montant_cotisation_mensuelle` déclaré
     *           par l'utilisateur à l'inscription ;
     *  - AMU (obligatoire, compte toujours) et cotisations personnalisées
     *    (comptent seulement si l'utilisateur les a adoptées) :
     *    `type_cotisations.montant_paiement_mensuel` de chaque type actif —
     *    source de vérité du suivi de conformité, distincte de
     *    `default_valeur` (qui ne sert qu'à pré-remplir le taux/montant d'une
     *    règle de prélèvement).
     *
     * `type_cotisations` est un catalogue commun à tous les utilisateurs
     * (plus de colonne `user_id`) : l'adoption d'un type par un utilisateur
     * se lit uniquement via l'existence d'une `ReglePrelevement` le liant à
     * ce type, jamais via une relation directe sur `type_cotisations`.
     */
    private function calculerObjectifMensuel(User $user): float
    {
        $objectifCnps = (float) ($user->declarationRevenu?->montant_cotisation_mensuelle ?? 0);

        // CNPS exclu ici : son objectif vient de declaration_revenus
        // ci-dessus, pas de `montant_paiement_mensuel` — l'inclure via
        // typeCotisationIdsConfigures() (qui le couvre, étant obligatoire)
        // compterait son objectif deux fois.
        $autresTypes = TypeCotisation::where('est_actif', true)
            ->where('code', '!=', 'CNPS')
            ->whereIn('id', $this->typeCotisationIdsConfigures($user->id))
            ->get();

        $objectifAutres = (float) $autresTypes->sum(
            fn (TypeCotisation $type) => (float) ($type->montant_paiement_mensuel ?? 0)
        );

        return $objectifCnps + $objectifAutres;
    }

    /**
     * Ids des `type_cotisations` réellement « configurés » par l'utilisateur :
     * obligatoires (CNPS/AMU, jamais désactivables) ou avec une
     * `ReglePrelevement` — jamais déterminé depuis le catalogue seul (voir
     * `PaiementService::calculerRepartition()`, corrigé pour la même raison).
     */
    private function typeCotisationIdsConfigures(string $userId): array
    {
        $typesAdoptesIds = ReglePrelevement::where('user_id', $userId)
            ->pluck('type_cotisation_id');

        return TypeCotisation::where('est_actif', true)
            ->where(function ($q) use ($typesAdoptesIds) {
                $q->where('est_obligatoire', true)->orWhereIn('id', $typesAdoptesIds);
            })
            ->pluck('id')
            ->all();
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

        $totalRecu        = $this->somme($operations, self::TYPES_RECU_GLOBAL);
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

    /**
     * Ventile les prélèvements de cotisation par type — uniquement les types
     * réellement configurés par l'utilisateur (jamais un type prélevé à tort,
     * voir `typeCotisationIdsConfigures()`), et nette chaque prélèvement des
     * remboursements qui lui sont liés (même effectués après la période
     * affichée — la traçabilité passe par `operation_id`, pas par la date).
     */
    private function ventilerCotisations(Collection $operations, string $userId): Collection
    {
        $operationsCotisation = $operations
            ->filter(fn ($op) => in_array($op->type_operation, self::TYPES_COTISATIONS) && $op->type_cotisation_id);

        if ($operationsCotisation->isEmpty()) {
            return collect();
        }

        $idsConfigures = $this->typeCotisationIdsConfigures($userId);

        $montantsRembourses = Remboursement::whereIn('operation_id', $operationsCotisation->pluck('id'))
            ->get()
            ->groupBy('operation_id')
            ->map(fn ($groupe) => (string) $groupe->sum('montant_rembourse'));

        return $operationsCotisation
            ->filter(fn ($op) => in_array($op->type_cotisation_id, $idsConfigures, true))
            ->groupBy('type_cotisation_id')
            ->map(function (Collection $groupe) use ($montantsRembourses) {
                $premier = $groupe->first();
                $type    = $premier->type_cotisation;

                $montantBrut = $groupe->reduce(
                    fn ($carry, $op) => bcadd($carry, (string) $op->montant, 2), '0.00'
                );
                $montantRembourse = $groupe->reduce(
                    fn ($carry, $op) => bcadd($carry, $montantsRembourses[$op->id] ?? '0.00', 2), '0.00'
                );
                $montantNet = bcsub($montantBrut, $montantRembourse, 2);
                $montantNet = bccomp($montantNet, '0.00', 2) > 0 ? $montantNet : '0.00';

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
                    'montant'        => $this->formater((float) $montantNet),
                ];
            })
            // Masque un type intégralement remboursé — plus rien de réellement conservé à afficher.
            ->filter(fn ($ligne) => (float) $ligne['montant'] > 0);
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
