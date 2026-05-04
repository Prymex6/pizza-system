<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactInquiryConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderMessage,
    ) {}

    public function envelope(): Envelope
    {
        $restaurantName = Setting::get('restaurant_name', config('app.name'));
        $fromAddress    = Setting::get('smtp_from_address') ?: config('mail.from.address');
        $fromName       = Setting::get('smtp_from_name') ?: $restaurantName;

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: 'Dziękujemy za wiadomość – ' . $restaurantName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.contact-inquiry-confirmation');
    }
}
