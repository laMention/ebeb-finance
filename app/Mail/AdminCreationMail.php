<?php

namespace App\Mail;

use App\Models\Administrateur;
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
        return new Envelope(
            subject: 'Vos accès au panneau d\'administration — ' . config('app.name'),
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
