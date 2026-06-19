<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\Operation;
use App\Models\PartenairesFinancier;
use App\Models\Reversement;
use App\Models\ReversementOperation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReversementAdminService
{
    // -------------------------------------------------------------------------
    // Mapping partenaire.type → types d'opérations éligibles
    // -------------------------------------------------------------------------

    private function typesOperations(string $typePartenaire): array
    {
        return match ($typePartenaire) {
            'CNPS'      => ['COTISATION_CNPS'],
            'AMU'       => ['COTISATION_AMU'],
            'ASSURANCE' => ['ASSURANCE_PERSONNALISEE'],
            default     => ['COTISATION_PERSONNALISEE'],
        };
    }

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function dashboard(): array
    {
        try {
            $maintenant    = Carbon::now();
            $debutMois     = $maintenant->copy()->startOfMonth();
            $debutMoisPrec = $maintenant->copy()->subMonth()->startOfMonth();
            $finMoisPrec   = $maintenant->copy()->subMonth()->endOfMonth();

            $totalReversi    = Reversement::where('statut', Reversement::STATUT_REVERSE)->sum('montant_total');
            $totalEnAttente  = Reversement::where('statut', Reversement::STATUT_EN_ATTENTE)->sum('montant_total');
            $nbTotal         = Reversement::count();
            $nbPartenaires   = Reversement::distinct()->whereNotNull('partenaires_financier_id')->count('partenaires_financier_id');
            $ceMois          = Reversement::where('date_reversement', '>=', $debutMois)->count();
            $moisPrec        = Reversement::whereBetween('date_reversement', [$debutMoisPrec, $finMoisPrec])->count();
            $evolution       = $moisPrec > 0 ? round((($ceMois - $moisPrec) / $moisPrec) * 100, 1) : ($ceMois > 0 ? 100 : 0);

            // Évolution mensuelle (12 derniers mois) — montant reversé
            $evolution12 = DB::table('reversements')
                ->selectRaw("DATE_FORMAT(date_reversement, '%Y-%m') as mois, SUM(montant_total) as montant, COUNT(*) as nb")
                ->where('statut', Reversement::STATUT_REVERSE)
                ->whereNull('deleted_at')
                ->where('date_reversement', '>=', $maintenant->copy()->subMonths(11)->startOfMonth())
                ->groupByRaw("DATE_FORMAT(date_reversement, '%Y-%m')")
                ->orderBy('mois')
                ->get()
                ->map(fn($r) => [
                    'mois'    => $r->mois,
                    'montant' => (float) $r->montant,
                    'nb'      => (int) $r->nb,
                ]);

            // Répartition par partenaire
            $parPartenaire = DB::table('reversements')
                ->join('partenaires_financiers', 'partenaires_financiers.id', '=', 'reversements.partenaires_financier_id')
                ->selectRaw('partenaires_financiers.nom, partenaires_financiers.type, partenaires_financiers.code, SUM(reversements.montant_total) as montant, COUNT(reversements.id) as nb')
                ->where('reversements.statut', Reversement::STATUT_REVERSE)
                ->whereNull('reversements.deleted_at')
                ->groupBy('partenaires_financiers.id', 'partenaires_financiers.nom', 'partenaires_financiers.type', 'partenaires_financiers.code')
                ->orderByDesc('montant')
                ->get()
                ->map(fn($r) => [
                    'nom'     => $r->nom,
                    'type'    => $r->type,
                    'code'    => $r->code,
                    'montant' => (float) $r->montant,
                    'nb'      => (int) $r->nb,
                    'couleur' => $this->couleurPartenaire($r->type),
                ]);

            // Répartition par type de cotisation (via partenaire.type)
            $parType = $parPartenaire->groupBy('type')->map(function ($items, $type) {
                return [
                    'type'    => $type,
                    'libelle' => $this->libellePartenaire($type),
                    'couleur' => $this->couleurPartenaire($type),
                    'montant' => $items->sum('montant'),
                    'nb'      => $items->sum('nb'),
                ];
            })->values();

            return [
                'success' => true,
                'data'    => [
                    'kpis' => [
                        'montant_total_reverse'   => (float) $totalReversi,
                        'montant_en_attente'       => (float) $totalEnAttente,
                        'nb_total'                 => $nbTotal,
                        'nb_partenaires'           => $nbPartenaires,
                        'reversements_ce_mois'     => $ceMois,
                        'evolution_mois_precedent' => $evolution,
                    ],
                    'evolution_mensuelle' => $evolution12,
                    'par_partenaire'     => $parPartenaire,
                    'par_type'           => $parType,
                ],
                'message' => 'Dashboard reversements',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    // -------------------------------------------------------------------------
    // Liste paginée
    // -------------------------------------------------------------------------

    public function lister(array $params): array
    {
        try {
            $query = Reversement::with(['partenaire'])
                ->withCount('reversementOperations');

            if (!empty($params['search'])) {
                $s = $params['search'];
                $query->where(function ($q) use ($s) {
                    $q->where('reference', 'like', "%{$s}%")
                      ->orWhereHas('partenaire', fn($p) => $p->where('nom', 'like', "%{$s}%"));
                });
            }

            if (!empty($params['partenaire_id'])) {
                $query->where('partenaires_financier_id', $params['partenaire_id']);
            }

            if (!empty($params['statut'])) {
                $query->where('statut', $params['statut']);
            }

            if (!empty($params['mois']) && !empty($params['annee'])) {
                $query->whereMonth('date_reversement', $params['mois'])
                      ->whereYear('date_reversement', $params['annee']);
            } elseif (!empty($params['annee'])) {
                $query->whereYear('date_reversement', $params['annee']);
            }

            if (!empty($params['date_debut'])) {
                $query->where('date_reversement', '>=', $params['date_debut']);
            }

            if (!empty($params['date_fin'])) {
                $query->where('date_reversement', '<=', $params['date_fin'] . ' 23:59:59');
            }

            $perPage   = min((int) ($params['per_page'] ?? 15), 100);
            $page      = (int) ($params['page'] ?? 1);
            $paginated = $query->orderByDesc('date_reversement')->paginate($perPage, ['*'], 'page', $page);

            $items = $paginated->getCollection()->map(fn(Reversement $r) => $this->formatItem($r));

            return [
                'success' => true,
                'message' => 'Liste des reversements',
                'data'    => $items,
                'meta'    => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    // -------------------------------------------------------------------------
    // Détail
    // -------------------------------------------------------------------------

    public function afficher(Reversement $reversement): array
    {
        try {
            $reversement->load(['partenaire', 'operations.user', 'operations.type_cotisation']);

            $peutAnnuler = false;
            $raisonBlocage = null;

            if ($reversement->statut === Reversement::STATUT_REVERSE || $reversement->statut === Reversement::STATUT_EN_ATTENTE) {
                $ref = $reversement->date_execution ?? $reversement->date_reversement;
                $diff = $ref ? Carbon::now()->diffInHours($ref, false) : -999;
                if (abs($diff) <= 48) {
                    $peutAnnuler = true;
                } else {
                    $raisonBlocage = 'Ce reversement ne peut plus être annulé car le délai de 48 heures est dépassé.';
                }
            } elseif ($reversement->statut === Reversement::STATUT_ANNULE) {
                $raisonBlocage = 'Ce reversement est déjà annulé.';
            } elseif ($reversement->statut === Reversement::STATUT_ECHEC) {
                $raisonBlocage = 'Ce reversement a échoué.';
            }

            $operations = $reversement->operations->map(fn(Operation $op) => [
                'id'              => $op->id,
                'reference'       => $op->reference,
                'type_operation'  => $op->type_operation,
                'montant'         => $op->montant,
                'statut'          => $op->statut,
                'date_operation'  => $op->date_operation?->format('Y-m-d'),
                'user'            => $op->user ? [
                    'id'        => $op->user->id,
                    'nom'       => $op->user->nom,
                    'prenom'    => $op->user->prenom,
                    'reference' => $op->user->reference ?? null,
                ] : null,
                'type_cotisation' => $op->type_cotisation ? [
                    'libelle'   => $op->type_cotisation->libelle,
                    'code'      => $op->type_cotisation->code,
                    'categorie' => $op->type_cotisation->categorie,
                ] : null,
            ]);

            return [
                'success' => true,
                'data'    => [
                    'reversement'   => [
                        'id'                => $reversement->id,
                        'reference'         => $reversement->reference,
                        'statut'            => $reversement->statut,
                        'montant_total'     => $reversement->montant_total,
                        'date_reversement'  => $reversement->date_reversement?->format('Y-m-d H:i'),
                        'date_execution'    => $reversement->date_execution?->format('Y-m-d H:i'),
                        'periode_debut'     => $reversement->periode_debut?->format('Y-m-d'),
                        'periode_fin'       => $reversement->periode_fin?->format('Y-m-d'),
                        'initie_par'        => $reversement->initie_par,
                        'annule_par'        => $reversement->annule_par,
                        'motif_annulation'  => $reversement->motif_annulation,
                        'created_at'        => $reversement->created_at?->format('Y-m-d H:i'),
                        'partenaire'        => $reversement->partenaire ? [
                            'id'   => $reversement->partenaire->id,
                            'nom'  => $reversement->partenaire->nom,
                            'code' => $reversement->partenaire->code,
                            'type' => $reversement->partenaire->type,
                        ] : null,
                        'nb_operations'    => $reversement->operations->count(),
                    ],
                    'operations'    => $operations,
                    'peut_annuler'  => $peutAnnuler,
                    'raison_blocage'=> $raisonBlocage,
                ],
                'message' => 'Détail du reversement',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    // -------------------------------------------------------------------------
    // Calculer le montant disponible à reverser
    // -------------------------------------------------------------------------

    public function calculerDisponible(array $params): array
    {
        try {
            $partenaire = PartenairesFinancier::findOrFail($params['partenaire_id']);
            $types      = $this->typesOperations($partenaire->type);

            $query = Operation::with(['user'])
                ->whereIn('type_operation', $types)
                ->where('statut', 'SUCCES')
                ->whereNotNull('operation_parent_id');

            if (!empty($params['periode_debut'])) {
                $query->where('date_operation', '>=', $params['periode_debut']);
            }
            if (!empty($params['periode_fin'])) {
                $query->where('date_operation', '<=', $params['periode_fin'] . ' 23:59:59');
            }

            // Exclure les opérations déjà reversées (dans un reversement non annulé)
            $query->whereNotIn('id', function ($sub) {
                $sub->select('operation_id')
                    ->from('reversement_operations')
                    ->join('reversements', 'reversements.id', '=', 'reversement_operations.reversement_id')
                    ->whereNotIn('reversements.statut', [Reversement::STATUT_ANNULE, Reversement::STATUT_ECHEC])
                    ->whereNull('reversement_operations.deleted_at')
                    ->whereNull('reversements.deleted_at');
            });

            $operations = $query->orderBy('date_operation')->get();

            $operationsFormatees = $operations->map(fn(Operation $op) => [
                'id'             => $op->id,
                'reference'      => $op->reference,
                'type_operation' => $op->type_operation,
                'montant'        => $op->montant,
                'date_operation' => $op->date_operation?->format('Y-m-d'),
                'user'           => $op->user ? [
                    'nom'    => $op->user->nom,
                    'prenom' => $op->user->prenom,
                ] : null,
            ]);

            return [
                'success' => true,
                'data'    => [
                    'partenaire'          => [
                        'id'   => $partenaire->id,
                        'nom'  => $partenaire->nom,
                        'type' => $partenaire->type,
                    ],
                    'montant_disponible'  => (float) $operations->sum('montant'),
                    'nb_operations'       => $operations->count(),
                    'operations'          => $operationsFormatees,
                ],
                'message' => 'Montant disponible calculé',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    // -------------------------------------------------------------------------
    // Créer un reversement
    // -------------------------------------------------------------------------

    public function creer(array $data, Administrateur $admin): array
    {
        try {
            $partenaire = PartenairesFinancier::findOrFail($data['partenaire_id']);
            $types      = $this->typesOperations($partenaire->type);

            // Trouver les opérations éligibles
            $query = Operation::whereIn('type_operation', $types)
                ->where('statut', 'SUCCES')
                ->whereNotNull('operation_parent_id');

            if (!empty($data['periode_debut'])) {
                $query->where('date_operation', '>=', $data['periode_debut']);
            }
            if (!empty($data['periode_fin'])) {
                $query->where('date_operation', '<=', $data['periode_fin'] . ' 23:59:59');
            }

            $query->whereNotIn('id', function ($sub) {
                $sub->select('operation_id')
                    ->from('reversement_operations')
                    ->join('reversements', 'reversements.id', '=', 'reversement_operations.reversement_id')
                    ->whereNotIn('reversements.statut', [Reversement::STATUT_ANNULE, Reversement::STATUT_ECHEC])
                    ->whereNull('reversement_operations.deleted_at')
                    ->whereNull('reversements.deleted_at');
            });

            $operations   = $query->get();
            $montantTotal = $operations->sum('montant');

            if ($operations->isEmpty()) {
                return ['success' => false, 'message' => 'Aucune opération éligible pour cette période et ce partenaire.', 'data' => null];
            }

            DB::transaction(function () use ($operations, $partenaire, $data, $admin, $montantTotal, &$reversement) {
                $reversement = Reversement::create([
                    'reference'                => 'REV-' . strtoupper(Str::random(8)),
                    'montant_total'            => $montantTotal,
                    'date_reversement'         => now(),
                    'statut'                   => Reversement::STATUT_EN_ATTENTE,
                    'initie_par'               => "{$admin->prenom} {$admin->nom}",
                    'partenaires_financier_id' => $partenaire->id,
                    'periode_debut'            => $data['periode_debut'] ?? null,
                    'periode_fin'              => $data['periode_fin'] ?? null,
                ]);

                $pivots = $operations->mapWithKeys(fn($op) => [
                    $op->id => ['montant' => $op->montant],
                ])->toArray();

                $reversement->operations()->attach($pivots);
            });

            return [
                'success' => true,
                'data'    => ['reversement' => $this->formatItem($reversement->load('partenaire'))],
                'message' => 'Reversement créé avec succès',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    // -------------------------------------------------------------------------
    // Annuler un reversement
    // -------------------------------------------------------------------------

    public function annuler(Reversement $reversement, array $data, Administrateur $admin): array
    {
        try {
            if (in_array($reversement->statut, [Reversement::STATUT_ANNULE, Reversement::STATUT_ECHEC])) {
                return ['success' => false, 'message' => 'Ce reversement ne peut pas être annulé (statut actuel : ' . $reversement->statut . ').', 'data' => null];
            }

            $ref  = $reversement->date_execution ?? $reversement->date_reversement;
            $diff = $ref ? Carbon::now()->diffInHours($ref, false) : 0;

            if (abs($diff) > 48) {
                return ['success' => false, 'message' => 'Ce reversement ne peut plus être annulé car le délai de 48 heures est dépassé.', 'data' => null];
            }

            $reversement->update([
                'statut'           => Reversement::STATUT_ANNULE,
                'motif_annulation' => $data['motif_annulation'] ?? null,
                'annule_par'       => "{$admin->prenom} {$admin->nom}",
            ]);

            return [
                'success' => true,
                'data'    => ['reversement' => $this->formatItem($reversement->fresh(['partenaire']))],
                'message' => 'Reversement annulé avec succès',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function formatItem(Reversement $r): array
    {
        return [
            'id'               => $r->id,
            'reference'        => $r->reference,
            'statut'           => $r->statut,
            'montant_total'    => $r->montant_total,
            'date_reversement' => $r->date_reversement?->format('Y-m-d H:i'),
            'date_execution'   => $r->date_execution?->format('Y-m-d H:i'),
            'periode_debut'    => $r->periode_debut?->format('Y-m-d'),
            'periode_fin'      => $r->periode_fin?->format('Y-m-d'),
            'initie_par'       => $r->initie_par,
            'annule_par'       => $r->annule_par,
            'motif_annulation' => $r->motif_annulation,
            'nb_operations'    => $r->reversement_operations_count ?? null,
            'partenaire'       => $r->relationLoaded('partenaire') && $r->partenaire ? [
                'id'   => $r->partenaire->id,
                'nom'  => $r->partenaire->nom,
                'code' => $r->partenaire->code,
                'type' => $r->partenaire->type,
            ] : null,
            'created_at'       => $r->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function couleurPartenaire(string $type): string
    {
        return match ($type) {
            'CNPS'      => '#3b82f6',
            'AMU'       => '#14b8a6',
            'ASSURANCE' => '#8b5cf6',
            'EPARGNE'   => '#10b981',
            default     => '#6b7280',
        };
    }

    private function libellePartenaire(string $type): string
    {
        return match ($type) {
            'CNPS'      => 'CNPS',
            'AMU'       => 'AMU',
            'ASSURANCE' => 'Assurance',
            'EPARGNE'   => 'Épargne',
            'REVERSEMENT' => 'Reversement',
            default     => 'Autre',
        };
    }
}
