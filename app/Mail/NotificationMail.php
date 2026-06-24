<?php

namespace App\Mail;

use App\Models\User;
use App\Services\EmailBrandingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public array $contenu;
    public string $type;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $type, array $contenu)
    {
        $this->user = $user;
        $this->type = $type;
        $this->contenu = $contenu;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $nom = EmailBrandingService::get()['nom_plateforme'];
        return new Envelope(
            subject: $this->contenu['sujet'] ?? 'ALERTE INFO '. $nom,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notifications',
            with: [
                'user' => $this->user,
                'type' => $this->type,
                'titre' => $this->contenu['titre'] ?? 'ALERTE INFO ',
                'corps' => $this->contenu['message'] ?? '',
                'contenu' => $this->contenu
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
