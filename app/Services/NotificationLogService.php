<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;

class NotificationLogService
{
    public function enregistrer(
        string  $canal,
        string  $typeNotification,
        string  $destinataire,
        string  $statut,
        ?string $sujet        = null,
        ?string $contenu      = null,
        ?string $messageErreur = null,
        ?string $userId       = null,
    ): NotificationLog {
        return NotificationLog::create([
            'canal'             => strtoupper($canal),
            'type_notification' => $typeNotification,
            'destinataire'      => $destinataire,
            'sujet'             => $sujet,
            'contenu'           => $contenu,
            'statut'            => $statut,
            'message_erreur'    => $messageErreur,
            'tentatives'        => 1,
            'envoye_a'          => $statut === 'ENVOYE' ? now() : null,
            'user_id'           => $userId,
        ]);
    }

    public function lister(array $params): array
    {
        $query = NotificationLog::with('user:id,nom,prenom,telephone,email');

        if (!empty($params['canal'])) {
            $query->where('canal', strtoupper($params['canal']));
        }

        if (!empty($params['statut'])) {
            $query->where('statut', strtoupper($params['statut']));
        }

        if (!empty($params['type_notification'])) {
            $query->where('type_notification', $params['type_notification']);
        }

        if (!empty($params['search'])) {
            $s = $params['search'];
            $query->where(fn($q) => $q
                ->where('destinataire', 'like', "%{$s}%")
                ->orWhere('sujet', 'like', "%{$s}%")
                ->orWhere('type_notification', 'like', "%{$s}%")
            );
        }

        if (!empty($params['date_debut'])) {
            $query->whereDate('created_at', '>=', $params['date_debut']);
        }

        if (!empty($params['date_fin'])) {
            $query->whereDate('created_at', '<=', $params['date_fin']);
        }

        $perPage   = min((int) ($params['per_page'] ?? 25), 100);
        $page      = max(1, (int) ($params['page'] ?? 1));
        $paginated = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return [
            'logs' => $paginated->getCollection()->map(fn($l) => [
                'id'               => $l->id,
                'canal'            => $l->canal,
                'type_notification'=> $l->type_notification,
                'destinataire'     => $l->destinataire,
                'sujet'            => $l->sujet,
                'statut'           => $l->statut,
                'message_erreur'   => $l->message_erreur,
                'tentatives'       => $l->tentatives,
                'envoye_a'         => $l->envoye_a?->toISOString(),
                'created_at'       => $l->created_at?->toISOString(),
                'user'             => $l->user ? [
                    'nom'       => $l->user->nom,
                    'prenom'    => $l->user->prenom,
                    'telephone' => $l->user->telephone,
                    'email'     => $l->user->email,
                ] : null,
            ])->toArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ];
    }

    public function reessayer(string $logId): array
    {
        $log = NotificationLog::findOrFail($logId);

        if ($log->statut !== 'ECHEC') {
            return ['success' => false, 'message' => 'Seules les notifications en échec peuvent être relancées.'];
        }

        $user = $log->user_id ? \App\Models\User::find($log->user_id) : null;

        // Tenter le renvoi via NotificationService
        try {
            $notifService = app(NotificationService::class);
            $contenu = ['titre' => $log->sujet ?? 'Notification', 'message' => $log->contenu ?? ''];

            if ($user) {
                $result = $notifService->envoyerNotification(
                    $user->id,
                    strtolower($log->canal),
                    $log->type_notification,
                    $contenu
                );
            } else {
                $result = ['success' => false, 'message' => 'Utilisateur introuvable pour le renvoi.'];
            }

            // Mettre à jour le log
            $log->increment('tentatives');
            if ($result['success']) {
                $log->update(['statut' => 'ENVOYE', 'message_erreur' => null, 'envoye_a' => now()]);
            }

            return $result;
        } catch (\Exception $e) {
            $log->increment('tentatives');
            $log->update(['message_erreur' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function compteurs(): array
    {
        return [
            'total'      => NotificationLog::count(),
            'envoyes'    => NotificationLog::where('statut', 'ENVOYE')->count(),
            'echecs'     => NotificationLog::where('statut', 'ECHEC')->count(),
            'en_attente' => NotificationLog::where('statut', 'EN_ATTENTE')->count(),
        ];
    }
}
