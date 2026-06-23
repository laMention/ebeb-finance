<?php

namespace App\Services;

use App\Models\Operation;
use App\Models\ReglePrelevement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepartitionAdminService
{
    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function dashboard(): array
    {
        try {
            // Base : opérations parent liées à un paiement entrant
            $totalRepartitions = Operation::whereNull('operation_parent_id')
                ->whereNotNull('paiement_entrant_id')
                ->count();

            $montantTotalRecu = (float) Operation::whereNull('operation_parent_id')
                ->whereNotNull('paiement_entrant_id')
                ->sum('montant');

            $nbSousOps        = Operation::whereNotNull('operation_parent_id')->count();
            $montantTotalReparti = (float) Operation::whereNotNull('operation_parent_id')->sum('montant');

            $allocationMoyenne   = $totalRepartitions > 0 ? $montantTotalRecu  / $totalRepartitions : 0;
            $nbMoyenPrelevements = $totalRepartitions > 0 ? $nbSousOps         / $totalRepartitions : 0;

            // Taux application règles auto
            $usersAvecPaiements = Operation::whereNull('operation_parent_id')
                ->whereNotNull('paiement_entrant_id')
                ->distinct('user_id')->count('user_id');

            $usersAvecRegles = ReglePrelevement::where('est_actif', true)
                ->distinct('user_id')->count('user_id');

            $tauxRegles = $usersAvecPaiements > 0
                ? round(($usersAvecRegles / $usersAvecPaiements) * 100, 1)
                : 0.0;

            // Répartition par type d'opération (sous_operations seulement)
            $parType = Operation::whereNotNull('operation_parent_id')
                ->selectRaw('type_operation, SUM(montant) as total_montant, COUNT(*) as nb')
                ->groupBy('type_operation')
                ->orderByDesc('total_montant')
                ->get();

            $totalParType = $parType->sum('total_montant');

            $allocationParCategorie = $parType->map(fn ($t) => [
                'type_operation' => $t->type_operation,
                'libelle'        => $this->libelleType($t->type_operation),
                'couleur'        => $this->couleurType($t->type_operation),
                'montant_total'  => (float) $t->total_montant,
                'nb'             => (int) $t->nb,
                'pourcentage'    => $totalParType > 0
                    ? round(($t->total_montant / $totalParType) * 100, 1)
                    : 0.0,
            ])->values();

            // Allocation moyenne détaillée (par paiement)
            $allocationMoyenneDetail = [
                'montant_moyen_recu'       => round($allocationMoyenne, 2),
                'montant_moyen_epargne'    => $this->montantMoyenTypes(['EPARGNE', 'PRELEVEMENT_EPARGNE'], $totalRepartitions),
                'montant_moyen_cotisation' => $this->montantMoyenTypes([
                    'COTISATION_CNPS', 'COTISATION_AMU', 'COTISATION_PERSONNALISEE',
                    'PRELEVEMENT_COTISATION',
                ], $totalRepartitions),
                'commission_moyenne'       => $this->montantMoyenTypes(['COMMISSION', 'COMMISSION_PLATEFORME'], $totalRepartitions),
                'montant_net_moyen'        => $totalRepartitions > 0
                    ? round(($montantTotalRecu - $montantTotalReparti) / $totalRepartitions, 2)
                    : 0.0,
            ];

            return [
                'success' => true,
                'message' => 'Dashboard répartitions récupéré',
                'data'    => [
                    'kpis' => [
                        'total_repartitions'              => $totalRepartitions,
                        'montant_total_recu'              => round($montantTotalRecu, 2),
                        'montant_total_reparti'           => round($montantTotalReparti, 2),
                        'allocation_moyenne_par_paiement' => round($allocationMoyenne, 2),
                        'nb_moyen_prelevements'           => round($nbMoyenPrelevements, 1),
                        'taux_application_regles'         => $tauxRegles,
                    ],
                    'allocation_par_categorie' => $allocationParCategorie,
                    'allocation_moyenne'       => $allocationMoyenneDetail,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Erreur dashboard répartitions', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Historique paginé
    // -------------------------------------------------------------------------

    public function lister(array $params): array
    {
        try {
            $query = Operation::with([
                'user:id,nom,prenom,telephone,reference',
                'paiement_entrant:id,montant_brut,operateur_source,reference_externe,statut',
            ])
            ->whereNull('operation_parent_id')
            ->whereNotNull('paiement_entrant_id')
            ->withCount('sous_operations')
            ->withSum('sous_operations', 'montant');

            if (!empty($params['search'])) {
                $s = $params['search'];
                $query->where(function ($q) use ($s) {
                    $q->where('reference', 'like', "%{$s}%")
                      ->orWhereHas('user', fn ($u) => $u
                          ->where('nom', 'like', "%{$s}%")
                          ->orWhere('prenom', 'like', "%{$s}%")
                          ->orWhere('reference', 'like', "%{$s}%")
                          ->orWhere('telephone', 'like', "%{$s}%")
                      );
                });
            }

            if (!empty($params['statut'])) {
                $query->where('statut', $params['statut']);
            }

            if (!empty($params['date_debut'])) {
                $query->whereDate('date_operation', '>=', $params['date_debut']);
            }

            if (!empty($params['date_fin'])) {
                $query->whereDate('date_operation', '<=', $params['date_fin']);
            }

            $perPage   = min((int) ($params['per_page'] ?? 15), 50);
            $paginator = $query->latest('date_operation')->latest('created_at')->paginate($perPage);

            $items = $paginator->getCollection()->map(fn ($op) => [
                'id'              => $op->id,
                'reference'       => $op->reference,
                'statut'          => $op->statut,
                'date_operation'  => $op->date_operation?->toISOString(),
                'created_at'      => $op->created_at?->toISOString(),
                'montant'         => (float) $op->montant,
                'nb_splits'       => (int)   ($op->sous_operations_count ?? 0),
                'montant_reparti' => (float)  ($op->sous_operations_sum_montant ?? 0),
                'montant_net'     => (float)  ($op->montant - ($op->sous_operations_sum_montant ?? 0)),
                'user'            => $op->user ? [
                    'id'        => $op->user->id,
                    'nom'       => $op->user->nom,
                    'prenom'    => $op->user->prenom,
                    'telephone' => $op->user->telephone,
                    'reference' => $op->user->reference,
                ] : null,
                'paiement_entrant' => $op->paiement_entrant ? [
                    'id'                => $op->paiement_entrant->id,
                    'montant_brut'      => (float) $op->paiement_entrant->montant_brut,
                    'operateur_source'  => $op->paiement_entrant->operateur_source,
                    'reference_externe' => $op->paiement_entrant->reference_externe,
                    'statut'            => $op->paiement_entrant->statut,
                ] : null,
            ])->values();

            return [
                'success' => true,
                'message' => 'Historique des répartitions récupéré',
                'data'    => $items,
                'meta'    => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Erreur listage répartitions', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Détail d'une répartition
    // -------------------------------------------------------------------------

    public function afficher(Operation $operation): array
    {
        try {
            $operation->load([
                'user:id,nom,prenom,telephone,reference',
                'paiement_entrant:id,montant_brut,operateur_source,reference_externe,statut',
                'sous_operations.type_cotisation:id,libelle,code,categorie',
                'sous_operations.objectif_epargne:id,libelle',
            ]);

            $totalReparti = (float) $operation->sous_operations->sum('montant');
            $montantNet   = (float) ($operation->montant - $totalReparti);

            $splits = $operation->sous_operations
                ->sortByDesc('montant')
                ->values()
                ->map(fn ($s) => [
                    'id'             => $s->id,
                    'reference'      => $s->reference,
                    'type_operation' => $s->type_operation,
                    'libelle'        => $s->libelle ?? $this->libelleType($s->type_operation),
                    'couleur'        => $this->couleurType($s->type_operation),
                    'montant'        => (float) $s->montant,
                    'pourcentage'    => $operation->montant > 0
                        ? round(($s->montant / $operation->montant) * 100, 1)
                        : 0.0,
                    'statut'         => $s->statut,
                    'type_cotisation' => $s->type_cotisation ? [
                        'libelle'   => $s->type_cotisation->libelle,
                        'code'      => $s->type_cotisation->code,
                        'categorie' => $s->type_cotisation->categorie,
                    ] : null,
                    'objectif_epargne' => $s->objectif_epargne ? [
                        'libelle' => $s->objectif_epargne->libelle,
                    ] : null,
                ]);

            return [
                'success' => true,
                'message' => 'Répartition récupérée',
                'data'    => [
                    'operation'     => [
                        'id'             => $operation->id,
                        'reference'      => $operation->reference,
                        'montant'        => (float) $operation->montant,
                        'statut'         => $operation->statut,
                        'description'    => $operation->description,
                        'date_operation' => $operation->date_operation?->toISOString(),
                        'created_at'     => $operation->created_at?->toISOString(),
                        'user'           => $operation->user ? [
                            'id'        => $operation->user->id,
                            'nom'       => $operation->user->nom,
                            'prenom'    => $operation->user->prenom,
                            'reference' => $operation->user->reference,
                            'telephone' => $operation->user->telephone,
                        ] : null,
                        'paiement_entrant' => $operation->paiement_entrant ? [
                            'id'                => $operation->paiement_entrant->id,
                            'montant_brut'      => (float) $operation->paiement_entrant->montant_brut,
                            'operateur_source'  => $operation->paiement_entrant->operateur_source,
                            'reference_externe' => $operation->paiement_entrant->reference_externe,
                            'statut'            => $operation->paiement_entrant->statut,
                        ] : null,
                    ],
                    'splits'        => $splits,
                    'total_reparti' => round($totalReparti, 2),
                    'montant_net'   => round($montantNet, 2),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Erreur affichage répartition', ['id' => $operation->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Règles de répartition (agrégées par type_cotisation)
    // -------------------------------------------------------------------------

    public function regles(): array
    {
        try {
            $rows = DB::table('regle_prelevements as rp')
                ->join('type_cotisations as tc', 'rp.type_cotisation_id', '=', 'tc.id')
                ->whereNull('rp.deleted_at')
                ->whereNull('tc.deleted_at')
                ->select([
                    'tc.id', 'tc.libelle', 'tc.code', 'tc.categorie',
                    'tc.est_obligatoire', 'tc.est_actif as tc_actif',
                ])
                ->selectRaw('COUNT(DISTINCT rp.user_id) as nb_utilisateurs')
                ->selectRaw('AVG(CASE WHEN rp.type_calcul = "POURCENTAGE" THEN rp.valeur ELSE NULL END) as pourcentage_moyen')
                ->selectRaw('SUM(CASE WHEN rp.est_actif = 1 THEN 1 ELSE 0 END) as nb_actifs')
                ->groupBy('tc.id', 'tc.libelle', 'tc.code', 'tc.categorie', 'tc.est_obligatoire', 'tc.est_actif')
                ->orderByDesc('nb_utilisateurs')
                ->get()
                ->map(fn ($r) => [
                    'id'               => $r->id,
                    'libelle'          => $r->libelle,
                    'code'             => $r->code,
                    'categorie'        => $r->categorie,
                    'est_obligatoire'  => (bool) $r->est_obligatoire,
                    'est_actif'        => (bool) $r->tc_actif,
                    'nb_utilisateurs'  => (int)  $r->nb_utilisateurs,
                    'pourcentage_moyen'=> $r->pourcentage_moyen !== null ? round((float) $r->pourcentage_moyen, 1) : null,
                    'nb_actifs'        => (int)  $r->nb_actifs,
                    'couleur'          => $this->couleurCategorie($r->categorie),
                ]);

            return [
                'success' => true,
                'message' => 'Règles de répartition récupérées',
                'data'    => $rows,
            ];
        } catch (\Exception $e) {
            Log::error('Erreur règles répartition', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    private function montantMoyenTypes(array $types, int $total): float
    {
        if ($total === 0) return 0.0;
        $sum = (float) Operation::whereNotNull('operation_parent_id')
            ->whereIn('type_operation', $types)->sum('montant');
        return round($sum / $total, 2);
    }

    private function libelleType(string $type): string
    {
        return match ($type) {
            'PAIEMENT_CLIENT'           => 'Paiement reçu',
            'EPARGNE', 'PRELEVEMENT_EPARGNE' => 'Épargne',
            'COTISATION_CNPS', 'PRELEVEMENT_COTISATION' => 'CNPS',
            'COTISATION_AMU'            => 'AMU',
            'COTISATION_PERSONNALISEE'  => 'Cotisation perso.',
            'ASSURANCE_PERSONNALISEE'   => 'Assurance',
            'COMMISSION_PLATEFORME'     => 'Commission',
            'COMMISSION'               => 'Commission',
            'REVERSEMENT'              => 'Reversement',
            'REVERSEMENT_ESCROW'        => 'Libér. escrow',
            'VIREMENT'                 => 'Virement',
            default                    => $type,
        };
    }

    private function couleurType(string $type): string
    {
        return match (true) {
            in_array($type, ['EPARGNE', 'PRELEVEMENT_EPARGNE'])                  => '#3B82F6',
            in_array($type, ['COTISATION_CNPS', 'PRELEVEMENT_COTISATION'])        => '#8B5CF6',
            $type === 'COTISATION_AMU'                                            => '#14B8A6',
            in_array($type, ['ASSURANCE_PERSONNALISEE', 'COTISATION_PERSONNALISEE']) => '#F97316',
            in_array($type, ['COMMISSION', 'COMMISSION_PLATEFORME'])               => '#EF4444',
            in_array($type, ['REVERSEMENT', 'REVERSEMENT_ESCROW'])                 => '#22C55E',
            default                                                               => '#9CA3AF',
        };
    }

    private function couleurCategorie(string $categorie): string
    {
        return match ($categorie) {
            'CNPS'          => '#8B5CF6',
            'AMU'           => '#14B8A6',
            'ASSURANCE'     => '#F97316',
            'PERSONNALISEE' => '#F59E0B',
            default         => '#9CA3AF',
        };
    }
}
