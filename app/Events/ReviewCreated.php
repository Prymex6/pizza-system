<?php

namespace App\Events;

use App\Models\Tenant\Review;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ReviewCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Review $review,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('reviews-manager')];
    }

    public function broadcastAs(): string
    {
        return 'review.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->review->id,
            'author_name' => $this->review->author_name,
            'rating'      => $this->review->rating,
            'preview'     => Str::limit($this->review->content, 80),
        ];
    }
}
