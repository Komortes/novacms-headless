<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DomainEventBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $envelope
     */
    public function __construct(
        public readonly array $envelope,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel((string) config('domain_events.broadcast.channel', 'novacms.domain-events')),
        ];
    }

    public function broadcastAs(): string
    {
        return 'domain.event';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->envelope;
    }
}
