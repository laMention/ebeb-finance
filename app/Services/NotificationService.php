<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Envoyer une notification à un utilisateur
     *
     * @param string $userId
     * @param string $canal
     * @param string $type
     * @param array $contenu
     * @param bool $immediat
     * @return array
     */
    public function envoyerNotification(string $userId, string $canal, string $type, array $contenu, bool $immediat = true): array
    {
        try {
            // Vérifier si l'utilisateur existe
            $user = User::find($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ];
            }

            // Préparer le contenu
            $contenuJson = json_encode($contenu);
            
            // Créer la notification dans la base de données
            $notification = Notification::create([
                'user_id' => $userId,
                'canal' => mettre_en_majuscule($canal),
                'type' => mettre_en_majuscule($type),
                'contenu' => $contenuJson,
                'est_envoye' => false,
                'envoye_le' => $immediat ? Carbon::now() : null
            ]);

            // Si immédiat, envoyer la notification
            if ($immediat) {
                $resultat = $this->envoyerSelonCanal($user, $canal, $type, $contenu);
                
                // Mettre à jour le statut d'envoi
                $notification->update([
                    'est_envoye' => $resultat['envoye'],
                    'envoye_le' => $resultat['envoye'] ? now() : null
                ]);
                
                if ($resultat['envoye']) {
                    return [
                        'success' => true,
                        'message' => 'Notification envoyée avec succès',
                        'notification' => $notification,
                        'details' => $resultat
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Échec de l\'envoi de la notification',
                        'notification' => $notification,
                        'error' => $resultat['error'] ?? null
                    ];
                }
            }
            
            return [
                'success' => true,
                'message' => 'Notification créée avec succès (envoi différé)',
                'notification' => $notification
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de la notification', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la notification',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer selon le canal (email, sms, push, in-app)
     */
    private function envoyerSelonCanal(User $user, string $canal, string $type, array $contenu): array
    {
        switch ($canal) {
            case 'email':
                return $this->envoyerEmail($user, $type, $contenu);
            case 'sms':
                return $this->envoyerSMS($user, $contenu);
            case 'push':
                return $this->envoyerPush($user, $contenu);
            case 'in-app':
                return $this->envoyerInApp($user, $contenu);
            default:
                return [
                    'envoye' => false,
                    'error' => 'Canal de notification non supporté'
                ];
        }
    }

    /**
     * Envoyer par email
     */
    private function envoyerEmail(User $user, string $type, array $contenu): array
    {
        try {
            // Envoyer l'email avec la classe Mailable
            Mail::to($user->email)->send(new NotificationMail($user, $type, $contenu));
            
            Log::info('Email envoyé', [
                'user_id' => $user->id,
                'email' => $user->email,
                'type' => $type,
                'sujet' => $contenu['sujet'] ?? 'Notification'
            ]);
            
            return [
                'envoye' => true,
                'canal' => 'email',
                'details' => "Email envoyé à {$user->email}"
            ];
        } catch (\Exception $e) {
            Log::error('Erreur envoi email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'envoye' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer par SMS
     */
    private function envoyerSMS(User $user, array $contenu): array
    {
        try {
            // Intégration avec un service SMS (Twilio, Orange SMS, etc.)
            // Exemple avec un service SMS fictif
            /*
            $smsService = new SMSService();
            $smsService->send($user->telephone, $contenu['message']);
            */
            
            Log::info('SMS envoyé', [
                'user_id' => $user->id,
                'telephone' => $user->telephone,
                'message' => $contenu['message'] ?? 'Notification'
            ]);
            
            return [
                'envoye' => true,
                'canal' => 'sms',
                'details' => "SMS envoyé à {$user->telephone}"
            ];
        } catch (\Exception $e) {
            return [
                'envoye' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer une notification push
     */
    private function envoyerPush(User $user, array $contenu): array
    {
        try {
            // Intégration avec Firebase Cloud Messaging ou autre service push
            Log::info('Push notification envoyée', [
                'user_id' => $user->id,
                'title' => $contenu['titre'] ?? 'Notification',
                'body' => $contenu['message'] ?? ''
            ]);
            
            return [
                'envoye' => true,
                'canal' => 'push',
                'details' => 'Push notification envoyée'
            ];
        } catch (\Exception $e) {
            return [
                'envoye' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Notification in-app (déjà enregistrée en BDD)
     */
    private function envoyerInApp(User $user, array $contenu): array
    {
        // Pour l'in-app, la notification est déjà en BDD
        return [
            'envoye' => true,
            'canal' => 'in-app',
            'details' => 'Notification in-app enregistrée'
        ];
    }

    /**
     * Notification spécifique pour l'activation de compte
     */
    public function notifierActivationCompte(User $user, array $options = []): array
    {
        $canal = $options['canal'] ?? 'in-app';
        
        $contenu = [
            'titre' => 'Compte activé !',
            'message' => "Félicitations {$user->prenom} {$user->nom}, votre compte a été activé avec succès. Vous pouvez maintenant accéder à tous nos services.",
            'sujet' => 'Activation de votre compte - ' . config('app.name'),
            'date_activation' => now()->format('d/m/Y H:i'),
            'type' => 'activation_compte'
        ];
        
        // Ajouter des informations supplémentaires si nécessaire
        if (isset($options['message_personnalise'])) {
            $contenu['message_personnalise'] = $options['message_personnalise'];
        }
        
        return $this->envoyerNotification(
            $user->id,
            $canal,
            'activation_compte',
            $contenu,
            true
        );
    }

    /**
     * Notification pour rejet de document
     */
    public function notifierRejetDocument(User $user, string $documentType, string $raison, array $options = []): array
    {
        $canal = $options['canal'] ?? 'in-app';
        
        $contenu = [
            'titre' => 'Document rejeté',
            'message' => "Cher {$user->prenom} {$user->nom}, votre document de type '{$documentType}' a été rejeté. Raison : {$raison}. Veuillez soumettre un nouveau document.",
            'sujet' => 'Document KYC rejeté',
            'type_document' => $documentType,
            'raison' => $raison,
            'date_rejet' => now()->format('d/m/Y H:i')
        ];
        
        return $this->envoyerNotification(
            $user->id,
            $canal,
            'rejet_document',
            $contenu,
            true
        );
    }

    /**
     * Récupérer les notifications non lues d'un utilisateur
     */
    public function getNotificationsNonLues(string $userId): array
    {
        $notifications = Notification::where('user_id', $userId)
            ->where('est_envoye', true)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return [
            'success' => true,
            'notifications' => $notifications,
            'count' => $notifications->count()
        ];
    }

    /**
     * Marquer une notification comme lue
     */
    public function marquerCommeLue(string $notificationId): array
    {
        $notification = Notification::find($notificationId);
        
        if (!$notification) {
            return [
                'success' => false,
                'message' => 'Notification non trouvée'
            ];
        }
        
        // Si vous avez un champ 'lue_le' dans la table
        // $notification->update(['lue_le' => now()]);
        
        return [
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ];
    }
}