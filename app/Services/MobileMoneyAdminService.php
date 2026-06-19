<?php

namespace App\Services;

use App\Http\Resources\MobileMoneyAdminResource;
use App\Models\CompteMobileMoney;
use Illuminate\Support\Facades\Log;

class MobileMoneyAdminService
{
    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function dashboard(): array
    {
        try {
            $total      = CompteMobileMoney::count();
            $actifs     = CompteMobileMoney::where('statut', 'ACTIF')->count();
            $en_attente = CompteMobileMoney::where('statut', 'EN_ATTENTE')->count();
            $suspendus  = CompteMobileMoney::where('statut', 'SUSPENDU')->count();
            $principaux = CompteMobileMoney::where('est_principal', true)->count();
            $secondaires= CompteMobileMoney::where('est_principal', false)->count();

            // Répartition par moyen de paiement / opérateur (avec statut API embarqué)
            $repartitions = CompteMobileMoney::selectRaw('moyen_paiement_id, operateur, COUNT(*) as nb_comptes')
                ->with(['moyen_paiement.configurationApiActive'])
                ->groupBy('moyen_paiement_id', 'operateur')
                ->orderByDesc('nb_comptes')
                ->get()
                ->map(function ($r) use ($total) {
                    $moyen  = $r->moyen_paiement;
                    $config = $moyen?->configurationApiActive;

                    return [
                        'moyen_paiement_id' => $r->moyen_paiement_id,
                        'operateur'         => $r->operateur,
                        'libelle'           => $moyen?->libelle ?? $r->operateur,
                        'logo'              => $moyen?->logo,
                        'nb_comptes'        => (int) $r->nb_comptes,
                        'pourcentage'       => $total > 0 ? round(($r->nb_comptes / $total) * 100, 1) : 0.0,
                        'statut_api'        => $config ? [
                            'est_actif'     => (bool) $config->est_actif,
                            'environnement' => $config->environnement,
                            'url_api'       => $config->url_api,
                        ] : null,
                    ];
                });

            return [
                'success' => true,
                'data' => [
                    'stats'      => compact('total', 'actifs', 'en_attente', 'suspendus', 'principaux', 'secondaires'),
                    'repartition'=> $repartitions,
                ],
                'message' => 'Dashboard Mobile Money récupéré',
            ];
        } catch (\Exception $e) {
            Log::error('Erreur dashboard Mobile Money', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Liste paginée
    // -------------------------------------------------------------------------

    public function lister(array $params): array
    {
        try {
            $query = CompteMobileMoney::with(['user', 'moyen_paiement.configurationApiActive', 'qrcode_paiement'])
                ->withCount('paiements_entrants');

            if (!empty($params['search'])) {
                $q = $params['search'];
                $query->where(function ($qb) use ($q) {
                    $qb->where('numero_compte', 'like', "%{$q}%")
                       ->orWhereHas('user', fn ($u) =>
                           $u->where('nom', 'like', "%{$q}%")
                             ->orWhere('prenom', 'like', "%{$q}%")
                             ->orWhere('reference', 'like', "%{$q}%")
                             ->orWhere('telephone', 'like', "%{$q}%")
                       );
                });
            }

            if (!empty($params['moyen_paiement_id'])) {
                $query->where('moyen_paiement_id', $params['moyen_paiement_id']);
            }

            if (!empty($params['operateur'])) {
                $query->where('operateur', $params['operateur']);
            }

            if (!empty($params['statut'])) {
                $query->where('statut', $params['statut']);
            }

            if (isset($params['est_principal']) && $params['est_principal'] !== '') {
                $query->where('est_principal', filter_var($params['est_principal'], FILTER_VALIDATE_BOOLEAN));
            }

            if (!empty($params['date_debut'])) {
                $query->whereDate('created_at', '>=', $params['date_debut']);
            }

            if (!empty($params['date_fin'])) {
                $query->whereDate('created_at', '<=', $params['date_fin']);
            }

            $perPage   = min((int) ($params['per_page'] ?? 15), 50);
            $paginator = $query->latest()->paginate($perPage);

            return [
                'success' => true,
                'message' => 'Comptes Mobile Money récupérés',
                'data'    => MobileMoneyAdminResource::collection($paginator->items())->toArray(request()),
                'meta'    => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Erreur listage comptes Mobile Money admin', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Détail d'un compte
    // -------------------------------------------------------------------------

    public function afficher(CompteMobileMoney $compte): array
    {
        try {
            $compte->load(['user', 'moyen_paiement.configurationApiActive', 'qrcode_paiement', 'paiements_entrants']);

            $paiements = $compte->paiements_entrants;

            $statsPaiements = [
                'nb_paiements'     => $paiements->count(),
                'montant_total'    => (float) $paiements->where('statut', 'SUCCES')->sum('montant_brut'),
                'dernier_paiement' => $paiements->sortByDesc('created_at')->first()?->created_at?->toISOString(),
            ];

            return [
                'success' => true,
                'message' => 'Compte Mobile Money récupéré',
                'data'    => [
                    'compte'          => new MobileMoneyAdminResource($compte),
                    'stats_paiements' => $statsPaiements,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Erreur affichage compte Mobile Money', ['id' => $compte->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Bascule de statut (admin)
    // -------------------------------------------------------------------------

    public function basculerStatut(CompteMobileMoney $compte): array
    {
        try {
            $nouvelStatut = match ($compte->statut) {
                'ACTIF'     => 'SUSPENDU',
                'SUSPENDU'  => 'ACTIF',
                'EN_ATTENTE'=> 'ACTIF',
                default     => 'SUSPENDU',
            };

            $compte->update([
                'statut'    => $nouvelStatut,
                'est_actif' => $nouvelStatut === 'ACTIF',
            ]);

            $compte->load(['user', 'moyen_paiement.configurationApiActive', 'qrcode_paiement']);

            Log::info('Statut compte Mobile Money changé', [
                'id'     => $compte->id,
                'statut' => $nouvelStatut,
            ]);

            return [
                'success' => true,
                'message' => "Compte {$nouvelStatut}",
                'data'    => new MobileMoneyAdminResource($compte),
            ];
        } catch (\Exception $e) {
            Log::error('Erreur bascule statut compte Mobile Money', ['id' => $compte->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }
}
