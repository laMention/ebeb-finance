<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\Ticket;
use App\Models\TicketHistorique;
use App\Models\User;
use Illuminate\Support\Str;

class TicketService
{
    public function __construct(private NotificationService $notificationService) {}

    public function lister(array $params): array
    {
        $query = Ticket::query()->with(['user:id,nom,prenom,telephone', 'traitePar:id,nom,prenom']);

        if (!empty($params['search'])) {
            $q = '%' . $params['search'] . '%';
            $query->where(function ($q2) use ($q) {
                $q2->where('reference', 'like', $q)
                    ->orWhere('objet', 'like', $q)
                    ->orWhere('description', 'like', $q);
            });
        }

        if (!empty($params['statut'])) {
            $query->where('statut', strtoupper($params['statut']));
        }

        if (!empty($params['severite'])) {
            $query->where('severite', strtoupper($params['severite']));
        }

        $perPage   = min((int) ($params['per_page'] ?? 20), 100);
        $page      = (int) ($params['page'] ?? 1);
        $paginated = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'data'    => $paginated->items(),
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ];
    }

    public function afficher(string $id): array
    {
        $ticket = Ticket::with([
            'user:id,nom,prenom,telephone,email',
            'traitePar:id,nom,prenom',
            'historique.modifiePar:id,nom,prenom',
        ])->findOrFail($id);

        return ['success' => true, 'data' => $ticket];
    }

    /**
     * Créé automatiquement depuis le formulaire mobile « Signaler un
     * problème » — objet dérivé de la description faute de champ dédié côté
     * mobile.
     */
    public function creerDepuisMobile(User $user, string $description): Ticket
    {
        $objet = Str::limit(trim(preg_replace('/\s+/', ' ', $description)), 80);

        $ticket = Ticket::create([
            'reference'   => Ticket::genererReference(),
            'user_id'     => $user->id,
            'objet'       => $objet,
            'description' => $description,
            'severite'    => 'NORMALE',
            'statut'      => 'OUVERT',
            'source'      => 'MOBILE',
        ]);

        TicketHistorique::create([
            'ticket_id'      => $ticket->id,
            'statut_nouveau' => 'OUVERT',
            'commentaire'    => 'Ticket créé automatiquement depuis l\'application mobile.',
        ]);

        AuditLogger::log('CREATE', null, 'tickets', $ticket->id, null, $ticket->toArray());

        // Alerte pour les administrateurs
        AlerteGenerator::systeme(
            'AVERTISSEMENT',
            'Nouveau ticket signalé depuis l\'application mobile',
            "L'utilisateur {$ticket->user->prenom} {$ticket->user->nom} a signalé un problème via l'application mobile. Référence du ticket : {$ticket->reference}."
        );


        return $ticket;
    }

    /**
     * Met à jour statut et/ou sévérité en une seule opération — une ligne
     * d'historique capture tout ce qui a changé. Notifie l'utilisateur si le
     * ticket passe au statut RESOLU.
     */
    public function modifier(Ticket $ticket, array $data, Administrateur $admin): array
    {
        $statutAvant   = $ticket->statut;
        $severiteAvant = $ticket->severite;

        $miseAJour = ['traite_par' => $admin->id];

        $statutChange   = array_key_exists('statut', $data) && $data['statut'] !== $statutAvant;
        $severiteChange = array_key_exists('severite', $data) && $data['severite'] !== $severiteAvant;

        if ($statutChange) {
            $miseAJour['statut'] = $data['statut'];
            $miseAJour['resolu_le'] = $data['statut'] === 'RESOLU' ? now() : null;
        }

        if ($severiteChange) {
            $miseAJour['severite'] = $data['severite'];
        }

        if (!$statutChange && !$severiteChange) {
            return ['success' => false, 'message' => 'Aucune modification à enregistrer.'];
        }

        $ticket->update($miseAJour);

        TicketHistorique::create([
            'ticket_id'            => $ticket->id,
            'statut_precedent'     => $statutChange ? $statutAvant : null,
            'statut_nouveau'       => $statutChange ? $data['statut'] : null,
            'severite_precedente'  => $severiteChange ? $severiteAvant : null,
            'severite_nouvelle'    => $severiteChange ? $data['severite'] : null,
            'commentaire'          => $data['commentaire'] ?? null,
            'modifie_par'          => $admin->id,
            'ip_adresse'           => request()->ip(),
        ]);

        AuditLogger::log(
            'TICKET.UPDATE',
            $admin,
            'tickets',
            $ticket->id,
            ['statut' => $statutAvant, 'severite' => $severiteAvant],
            ['statut' => $ticket->statut, 'severite' => $ticket->severite]
        );

        if ($statutChange && $data['statut'] === 'RESOLU' && $ticket->user) {
            $this->notificationService->notifierTicketResolu(
                $ticket->user,
                $ticket->reference,
                $data['commentaire'] ?? null
            );
        }

        return ['success' => true, 'data' => $ticket->fresh(['user:id,nom,prenom,telephone', 'traitePar:id,nom,prenom'])];
    }

    public function supprimer(Ticket $ticket, Administrateur $admin): array
    {
        $avant = $ticket->toArray();
        $ticket->delete();

        AuditLogger::log('DELETE', $admin, 'tickets', $ticket->id, $avant, null);

        return ['success' => true, 'message' => 'Ticket supprimé.'];
    }
}
