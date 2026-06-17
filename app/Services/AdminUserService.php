<?php

namespace App\Services;

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
}
