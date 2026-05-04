<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Reservation;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public string $event = 'created' // created | confirmed | cancelled
    ) {}

    public function envelope(): Envelope
    {
        $restaurantName = Setting::get('restaurant_name', config('app.name'));
        $fromAddress    = Setting::get('smtp_from_address') ?: config('mail.from.address');
        $fromName       = Setting::get('smtp_from_name') ?: $restaurantName;

        $subject = match ($this->event) {
            'confirmed'  => 'Rezerwacja potwierdzona – ' . $restaurantName,
            'cancelled'  => 'Rezerwacja anulowana – ' . $restaurantName,
            default      => 'Potwierdzenie rezerwacji – ' . $restaurantName,
        };

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.reservation');
    }
}
