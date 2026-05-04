<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoyaltyBirthdayMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Customer $customer,
        public int $bonusPoints
    ) {}

    public function envelope(): Envelope
    {
        $restaurantName = Setting::get('restaurant_name', config('app.name'));
        $fromAddress    = Setting::get('smtp_from_address') ?: config('mail.from.address');

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $restaurantName) : null,
            subject: 'Urodzinowa niespodzianka od ' . $restaurantName . '!',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.loyalty-birthday');
    }
}
