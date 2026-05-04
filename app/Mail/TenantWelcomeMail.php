<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $ownerName,
        public string $restaurantName,
        public string $loginUrl,
        public string $password,
        public string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Witaj w ' . config('app.name') . " – dostęp do {$this->restaurantName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-welcome',
        );
    }
}
