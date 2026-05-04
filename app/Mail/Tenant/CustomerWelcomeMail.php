<?php

namespace App\Mail\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Tenant\Setting;

class CustomerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $customerName,
        public readonly string $restaurantName,
    ) {}

    public function envelope(): Envelope
    {
        $from = Setting::get('smtp_from_address') ?: config('mail.from.address');
        $fromName = Setting::get('smtp_from_name') ?: Setting::get('restaurant_name', 'Restauracja');

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($from, $fromName),
            subject: 'Witamy w ' . $this->restaurantName . '!',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.customer-welcome');
    }
}
