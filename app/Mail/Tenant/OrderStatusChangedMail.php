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

class OrderStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $trackingUrl;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus,
        ?string $trackingUrl = null
    ) {
        $token = $order->tracking_token ?? \App\Http\Controllers\Tenant\Client\OrderTrackingController::generateToken($order->order_number);
        $this->trackingUrl = $trackingUrl ?? (url('/zamowienie/' . $order->order_number . '/sledzenie') . '?token=' . $token);
    }

    public function envelope(): Envelope
    {
        $restaurantName = Setting::get('restaurant_name', config('app.name'));
        $statusLabels = [
            'paid'        => 'przyjęte',
            'preparing'   => 'w przygotowaniu',
            'ready'       => 'gotowe',
            'on_delivery' => 'w drodze',
            'completed'   => 'zrealizowane',
            'cancelled'   => 'anulowane',
        ];
        $label = $statusLabels[$this->newStatus] ?? $this->newStatus;
        $fromAddress = Setting::get('smtp_from_address') ?: config('mail.from.address');
        $fromName    = Setting::get('smtp_from_name') ?: $restaurantName;

        return new Envelope(
            from: $fromAddress ? new Address($fromAddress, $fromName) : null,
            subject: 'Zamówienie #' . $this->order->order_number . ' – ' . $label . ' | ' . $restaurantName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant.order-status-changed');
    }
}
