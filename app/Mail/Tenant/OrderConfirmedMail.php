<?php

namespace App\Mail\Tenant;

use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $trackingUrl;
    public ?string $estimatedTimeFormatted;

    public function __construct(public Order $order, ?string $trackingUrl = null, ?string $estimatedTimeFormatted = null)
    {
        $token = \App\Http\Controllers\Tenant\Client\OrderTrackingController::generateToken($order->order_number);
        $this->trackingUrl = $trackingUrl ?? (url('/zamowienie/' . $order->order_number . '/sledzenie') . '?token=' . $token);
        $this->estimatedTimeFormatted = $estimatedTimeFormatted;
    }

    public function envelope(): Envelope
    {
        $restaurantName = Setting::get('restaurant_name', config('app.name'));
        $fromAddress = Setting::get('smtp_from_address') ?: config('mail.from.address');
        $fromName    = Setting::get('smtp_from_name') ?: $restaurantName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: 'Potwierdzenie zamówienia #' . $this->order->order_number . ' – ' . $restaurantName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.order-confirmed');
    }
}
