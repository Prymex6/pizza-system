<?php

namespace App\Events;

use App\Models\Tenant\Reservation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Reservation $reservation
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('reservations-manager'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'reservation.created';
    }

    public function broadcastWith(): array
    {
        return [
            'reservation' => [
                'id'               => $this->reservation->id,
                'customer_name'    => $this->reservation->customer_name,
                'customer_phone'   => $this->reservation->customer_phone,
                'reservation_date' => $this->reservation->reservation_date,
                'reservation_time' => substr($this->reservation->reservation_time, 0, 5),
                'party_size'       => $this->reservation->party_size,
                'notes'            => $this->reservation->notes,
                'status'           => $this->reservation->status,
            ],
        ];
    }
}
