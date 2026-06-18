<?php

namespace App\Services;

use App\Models\Cotisation;
use App\Models\ObjectifEpargne;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminUserService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Liste paginée des utilisateurs avec filtres, recherche et pagination serveur.
     */
    public function listerUtilisateurs(array $params): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['documentKYCs', 'latestPortefeuille'])
            ->withCount('paiementsEntrants');

        // Recherche textuelle multi-champs
        if (!empty($params['recherche'])) {
            $r = $params['recherche'];
            $query->where(function ($q) use ($r) {
                $q->where('nom', 'like', "%{$r}%")
                  ->orWhere('prenom', 'like', "%{$r}%")
                  ->orWhere('telephone', 'like', "%{$r}%")
                  ->orWhere('reference', 'like', "%{$r}%")
                  ->orWhere('email', 'like', "%{$r}%")
                  ->orWhere('profession', 'like', "%{$r}%");
            });
        }

        // Filtres scalaires
        if (!empty($params['statut'])) {
            $query->where('statut', $params['statut']);
        }
        if (!empty($params['type_carte'])) {
            $query->where('type_carte', $params['type_carte']);
        }
        if (!empty($params['sexe'])) {
            $query->where('sexe', $params['sexe']);
        }
        if (!empty($params['ville'])) {
            $query->where('ville', 'like', "%{$params['ville']}%");
        }
        if (!empty($params['profession'])) {
            $query->where('profession', 'like', "%{$params['profession']}%");
        }

        // Filtre période d'inscription
        if (!empty($params['date_debut'])) {
            $query->whereDate('created_at', '>=', $params['date_debut']);
        }
        if (!empty($params['date_fin'])) {
            $query->whereDate('created_at', '<=', $params['date_fin']);
        }

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 20;
        $page    = isset($params['page'])     ? (int) $params['page']     : 1;

        return $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Compteurs pour les filtres rapides (onglets de la liste).
     */
    public function compterParFiltre(): array
    {
        return [
            'total'      => User::count(),
            'actif'      => User::where('statut', 'ACTIF')->count(),
            'suspendu'   => User::where('statut', 'SUSPENDU')->count(),
            'en_attente' => User::where('statut', 'EN_ATTENTE')->count(),
            'vip'        => User::where('type_carte', 'VIP')->count(),
            'basic'      => User::where('type_carte', 'BASIC')->count(),
        ];
    }

    /**
     * Charge un utilisateur complet avec toutes ses relations pour la page détail.
     */
    public function obtenirUtilisateur(User $user): User
    {
        return $user->load([
            'informationProfessionnelle',
            'documentKYCs',
            'declarationRevenu',
            'compteMobileMoneys',
            'enfants',
            'cotisations.typeCotisation',
            'escrows.operation',
            'operations',
            'paiementsEntrants',
            'reglePrelevements',
            'latestPortefeuille',
        ]);
    }

    /**
     * Calcule le statut KYC global à partir des documents de l'utilisateur.
     * L'utilisateur doit avoir ses documentKYCs déjà chargés.
     */
    public function calculerStatutKYC(User $user): string
    {
        $docs = $user->documentKYCs;

        if ($docs->isEmpty()) {
            return 'EN_ATTENTE';
        }
        if ($docs->contains('statut', 'REJETE')) {
            return 'REJETE';
        }
        if ($docs->every(fn($d) => $d->statut === 'VALIDE')) {
            return 'VALIDE';
        }

        return 'EN_ATTENTE';
    }

    /**
     * Suspend temporairement un compte utilisateur.
     */
    public function suspendreCompte(User $user, ?string $motif = null): array
    {
        if ($user->statut === 'SUSPENDU') {
            return ['success' => false, 'message' => 'Le compte est déjà suspendu.'];
        }

        $user->update(['statut' => 'SUSPENDU']);

        $message = $motif
            ? "Votre compte a été suspendu. Motif : {$motif}."
            : "Votre compte a été temporairement suspendu. Contactez le support pour plus d'informations.";

        $this->notificationService->envoyerNotification(
            $user->id,
            'in-app',
            'SUSPENSION_COMPTE',
            [
                'titre'   => 'Compte suspendu',
                'message' => $message,
                'sujet'   => 'Suspension de compte — ' . config('app.name'),
                'motif'   => $motif ?? '',
            ],
            true
        );

        return ['success' => true, 'message' => 'Compte suspendu avec succès.', 'user' => $user->fresh()];
    }

    /**
     * Réactive un compte suspendu.
     */
    public function reactiverCompte(User $user): array
    {
        if ($user->statut !== 'SUSPENDU') {
            return ['success' => false, 'message' => "Le compte n'est pas suspendu."];
        }

        $user->update(['statut' => 'ACTIF']);

        $this->notificationService->envoyerNotification(
            $user->id,
            'in-app',
            'REACTIVATION_COMPTE',
            [
                'titre'   => 'Compte réactivé',
                'message' => 'Votre compte a été réactivé avec succès. Vous pouvez à nouveau accéder à la plateforme.',
                'sujet'   => 'Réactivation de compte — ' . config('app.name'),
            ],
            true
        );

        return ['success' => true, 'message' => 'Compte réactivé avec succès.', 'user' => $user->fresh()];
    }

    /**
     * Archive (soft-delete) un compte utilisateur.
     * L'utilisateur n'apparaît plus dans les listes standards.
     */
    public function archiverCompte(User $user): array
    {
        $user->delete();

        return ['success' => true, 'message' => 'Compte archivé avec succès.'];
    }

    /**
     * Met à jour les informations administratives d'un utilisateur
     * (numéro CNPS, numéro CMU/AMU).
     */
    public function mettreAjourInfosAdmin(User $user, array $data): array
    {
        $champs = [];

        if (array_key_exists('numero_cnps', $data)) {
            $champs['numero_cnps'] = $data['numero_cnps'] ?: null;
        }
        if (array_key_exists('numero_cmu', $data)) {
            $champs['numero_cmu'] = $data['numero_cmu'] ?: null;
        }

        if (empty($champs)) {
            return ['success' => false, 'message' => 'Aucune information à mettre à jour.'];
        }

        $user->update($champs);

        return ['success' => true, 'message' => 'Informations mises à jour avec succès.', 'user' => $user->fresh()];
    }

    /**
     * Réinitialise le code PIN d'un utilisateur.
     * Génère un nouveau code à 6 chiffres, met à jour le mot de passe hashé,
     * et envoie une notification in-app (+ email si disponible).
     */
    public function reinitialiserCodePin(User $user): array
    {
        $nouveauPin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Le champ password est casté 'hashed' dans User::casts()
        $user->update(['password' => $nouveauPin]);

        $contenu = [
            'titre'       => 'Code PIN réinitialisé',
            'message'     => "Votre code PIN a été réinitialisé par l'administrateur. Votre nouveau code PIN temporaire est : {$nouveauPin}. Veuillez le modifier dès votre prochaine connexion.",
            'sujet'       => 'Réinitialisation de votre code PIN — ' . config('app.name'),
            'nouveau_pin' => $nouveauPin,
        ];

        // Notification in-app (toujours)
        $this->notificationService->envoyerNotification(
            $user->id,
            'in-app',
            'REINITIALISATION_PIN',
            $contenu,
            true
        );

        // Envoi par SMS si le canal existe (à implementer)

        // Email si l'utilisateur en possède un
        if ($user->email) {
            $this->notificationService->envoyerNotification(
                $user->id,
                'email',
                'REINITIALISATION_PIN',
                $contenu,
                true
            );
        }

        return ['success' => true, 'message' => 'Code PIN réinitialisé avec succès. L\'utilisateur a été notifié.'];
    }

    /**
     * Retourne la vue consolidée des cotisations d'un utilisateur pour une année donnée :
     * - statut annuel global (total versé / objectif / pourcentage)
     * - progression par type de cotisation
     * - objectifs d'épargne
     * - historique mensuel des versements
     */
    public function getCotisations(User $user, int $annee): array
    {
        $cotisations = Cotisation::with('typeCotisation')
            ->where('user_id', $user->id)
            ->where('annee', $annee)
            ->orderBy('mois')
            ->get();

        // — Progression par type de cotisation —
        $parType            = $cotisations->groupBy('type_cotisation_id');
        $progressionParType = [];

        foreach ($parType as $_typeId => $groupe) {
            $type = $groupe->first()->typeCotisation;
            if (!$type) continue;

            $verse    = (float) $groupe->sum('montant_verse');
            $objectif = (float) $groupe->sum('montant_objectif');
            $restant  = (float) $groupe->sum('montant_restant');
            $pct      = $objectif > 0 ? round(($verse / $objectif) * 100, 2) : 0;

            $progressionParType[] = [
                'type_cotisation_uuid' => $type->id,
                'libelle'              => $type->libelle,
                'code'                 => $type->code,
                'categorie'            => $type->categorie,
                'est_personnalisee'    => !is_null($type->user_id),
                'est_obligatoire'      => (bool) $type->est_obligatoire,
                'montant_verse'        => $verse,
                'montant_objectif'     => $objectif,
                'montant_restant'      => $restant,
                'pourcentage'          => $pct,
                'statut'               => $pct >= 100 ? 'A_JOUR' : ($pct >= 50 ? 'PARTIEL' : 'EN_RETARD'),
                'nb_versements'        => $groupe->count(),
            ];
        }

        // — Objectifs d'épargne (tous, pas filtrés par année) —
        $objectifs = ObjectifEpargne::where('user_id', $user->id)
            ->get()
            ->map(function ($obj) {
                $pct = $obj->montant_cible > 0
                    ? round(($obj->montant_epargne / $obj->montant_cible) * 100, 2)
                    : 0;
                return [
                    'uuid'            => $obj->id,
                    'libelle'         => $obj->libelle,
                    'montant_cible'   => (float) $obj->montant_cible,
                    'montant_epargne' => (float) $obj->montant_epargne,
                    'pourcentage'     => $pct,
                    'est_actif'       => (bool) $obj->est_actif,
                    'date_limite'     => $obj->date_limite?->format('Y-m-d'),
                    'type_calcul'     => $obj->type_calcul,
                    'valeur'          => $obj->valeur !== null ? (float) $obj->valeur : null,
                ];
            })
            ->values()
            ->toArray();

        // — Historique mensuel —
        $moisLabels = [
            1 => 'Janvier', 2 => 'Février',  3 => 'Mars',      4 => 'Avril',
            5 => 'Mai',     6 => 'Juin',      7 => 'Juillet',   8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        $parMois    = $cotisations->groupBy('mois');
        $historique = [];

        for ($m = 1; $m <= 12; $m++) {
            $groupe = $parMois->get($m);
            if (!$groupe || $groupe->isEmpty()) continue;

            $versements = $groupe->map(function ($c) {
                $type = $c->typeCotisation;
                $pct  = $c->montant_objectif > 0
                    ? round(($c->montant_verse / $c->montant_objectif) * 100, 2)
                    : 0;
                return [
                    'libelle'          => $type?->libelle ?? '—',
                    'code'             => $type?->code ?? '',
                    'est_personnalisee'=> $type ? !is_null($type->user_id) : false,
                    'montant_verse'    => (float) $c->montant_verse,
                    'montant_objectif' => (float) $c->montant_objectif,
                    'montant_restant'  => (float) $c->montant_restant,
                    'pourcentage'      => $pct,
                    'statut'           => $c->statut,
                    'date_paiement'    => $c->date_paiement?->format('Y-m-d'),
                ];
            })->values()->toArray();

            $historique[] = [
                'mois'         => $m,
                'annee'        => $annee,
                'mois_libelle' => $moisLabels[$m] . ' ' . $annee,
                'total_verse'  => (float) $groupe->sum('montant_verse'),
                'cotisations'  => $versements,
            ];
        }

        // — Statut annuel global —
        $totalVerse    = (float) $cotisations->sum('montant_verse');
        $totalObjectif = (float) $cotisations->sum('montant_objectif');
        $pctGlobal     = $totalObjectif > 0 ? round(($totalVerse / $totalObjectif) * 100, 2) : 0;

        return [
            'annee'                => $annee,
            'statut_annuel'        => [
                'total_verse'    => $totalVerse,
                'total_objectif' => $totalObjectif,
                'pourcentage'    => $pctGlobal,
                'statut'         => $pctGlobal >= 100 ? 'A_JOUR' : ($pctGlobal >= 50 ? 'PARTIEL' : 'EN_RETARD'),
            ],
            'progression_par_type' => $progressionParType,
            'objectifs_epargne'    => $objectifs,
            'historique_mensuel'   => $historique,
        ];
    }
}
