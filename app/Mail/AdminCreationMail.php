<?php

namespace App\Mail;

use App\Models\Administrateur;
use App\Services\EmailBrandingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminCreationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Administrateur $admin,
        public readonly string         $plainPassword,
        public readonly string         $panelUrl,
        public readonly string         $dateCreation,
    ) {}

    public function envelope(): Envelope
    {
        $nom = EmailBrandingService::get()['nom_plateforme'];

        return new Envelope(
            subject: "Vos accès au panneau d'administration — {$nom}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin-creation');
    }

    public function attachments(): array
    {
        return [];
    }
}
