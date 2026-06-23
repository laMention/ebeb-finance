<?php

namespace App\Services;

use App\Mail\AdminCreationMail;
use App\Models\Administrateur;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminNotificationService
{
    private const TYPE = 'INVITATION_ADMIN';
    private const SUJET_INVITATION = "Vos accès au panneau d'administration";

    /**
     * Envoyer l'email d'invitation lors de la création d'un administrateur.
     * Vérifie les flags globaux et la config du canal avant l'envoi.
     * Enregistre le résultat dans l'historique des notifications.
     */
    public function envoyerInvitation(Administrateur $admin, string $plainPassword): array
    {
        // Vérifier le flag global NOTIF_EMAIL
        if (!ParametreGlobalService::estActif('NOTIF_EMAIL')) {
            return [
                'envoye' => false,
                'raison' => 'Les notifications email sont désactivées dans les paramètres globaux.',
            ];
        }

        // Vérifier si le canal EMAIL est actif dans la configuration des notifications
        try {
            if (!app(NotificationConfigService::class)->estActif('EMAIL')) {
                return [
                    'envoye' => false,
                    'raison' => 'Le canal email est désactivé dans la configuration des notifications.',
                ];
            }
        } catch (\Exception $e) {
            Log::warning("Impossible de vérifier le canal EMAIL pour l'invitation admin.", ['error' => $e->getMessage()]);
        }

        $panelUrl      = config('app.admin_panel_url', config('app.url') . '/admin');
        $dateCreation  = now()->format('d/m/Y à H:i');
        $sujet         = self::SUJET_INVITATION . ' — ' . config('app.name');

        try {
            Mail::to($admin->email)->send(new AdminCreationMail($admin, $plainPassword, $panelUrl, $dateCreation));

            $this->journaliser(
                destinataire: $admin->email,
                sujet: $sujet,
                contenu: "Invitation envoyée à {$admin->prenom} {$admin->nom}",
                statut: 'ENVOYE',
            );

            Log::info('Email invitation admin envoyé.', ['admin_id' => $admin->id, 'email' => $admin->email]);

            return ['envoye' => true];

        } catch (\Exception $e) {
            Log::error("Échec envoi email invitation admin.", [
                'admin_id' => $admin->id,
                'email'    => $admin->email,
                'error'    => $e->getMessage(),
            ]);

            $this->journaliser(
                destinataire: $admin->email,
                sujet: $sujet,
                contenu: null,
                statut: 'ECHEC',
                erreur: $e->getMessage(),
            );

            return ['envoye' => false, 'erreur' => $e->getMessage()];
        }
    }

    /**
     * Renvoyer l'email d'invitation en générant un nouveau mot de passe temporaire.
     * Le mot de passe en base est réinitialisé.
     */
    public function renvoyerInvitation(Administrateur $admin): array
    {
        $nouveauMotDePasse = $this->genererMotDePasseTemporaire();
        $admin->update(['password' => $nouveauMotDePasse]); // cast 'hashed' → hashé automatiquement

        return $this->envoyerInvitation($admin, $nouveauMotDePasse);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function genererMotDePasseTemporaire(): string
    {
        // 12 caractères : lettres (maj/min) + chiffres + symboles simples
        $jeu = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        return implode('', array_map(
            fn() => $jeu[random_int(0, strlen($jeu) - 1)],
            range(1, 12)
        ));
    }

    private function journaliser(
        string  $destinataire,
        string  $sujet,
        ?string $contenu,
        string  $statut,
        ?string $erreur = null,
    ): void {
        try {
            app(NotificationLogService::class)->enregistrer(
                canal:            'EMAIL',
                typeNotification: self::TYPE,
                destinataire:     $destinataire,
                statut:           $statut,
                sujet:            $sujet,
                contenu:          $contenu,
                messageErreur:    $erreur,
                userId:           null,
            );
        } catch (\Exception $e) {
            Log::warning('Impossible de journaliser la notification admin.', ['error' => $e->getMessage()]);
        }
    }
}
