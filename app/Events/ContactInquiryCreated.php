<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ContactInquiryCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $message,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('contact-admin')];
    }

    public function broadcastAs(): string
    {
        return 'contact.inquiry';
    }

    public function broadcastWith(): array
    {
        return [
            'name'    => $this->name,
            'email'   => $this->email,
            'preview' => Str::limit($this->message, 80),
        ];
    }
}
