<?php

namespace App\Events;

use App\Models\Tenant\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public float $latitude,
        public float $longitude,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('order.' . $this->order->order_number)];
    }

    public function broadcastAs(): string
    {
        return 'driver.location-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id'  => $this->order->id,
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
