<?php

namespace App\Services;

use App\Events\DomainEventBroadcasted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

class DomainEventPublisher
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function publish(string $name, array $payload = []): array
    {
        $envelope = [
            'event_id' => (string) Str::uuid(),
            'name' => $name,
            'occurred_at' => now()->toIso8601String(),
            'payload' => $payload,
        ];

        $this->publishRealtime($envelope);
        $this->publishToStream($envelope);

        return $envelope;
    }

    /**
     * @param  array<string, mixed>  $envelope
     */
    private function publishRealtime(array $envelope): void
    {
        if (! (bool) config('domain_events.broadcast.enabled', true)) {
            return;
        }

        Event::dispatch(new DomainEventBroadcasted($envelope));
    }

    /**
     * @param  array<string, mixed>  $envelope
     */
    private function publishToStream(array $envelope): void
    {
        if (! (bool) config('domain_events.stream.enabled', true)) {
            return;
        }

        try {
            $stream = (string) config('domain_events.stream.name', 'novacms:domain-events');
            $payload = json_encode(
                $envelope['payload'] ?? [],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ) ?: '{}';

            Redis::command('XADD', [
                $stream,
                '*',
                'event_id',
                (string) $envelope['event_id'],
                'name',
                (string) $envelope['name'],
                'occurred_at',
                (string) $envelope['occurred_at'],
                'payload',
                $payload,
            ]);

            $maxlen = (int) config('domain_events.stream.maxlen', 0);
            if ($maxlen > 0) {
                Redis::command('XTRIM', [
                    $stream,
                    'MAXLEN',
                    '~',
                    (string) $maxlen,
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Domain event stream publish failed.', [
                'name' => $envelope['name'] ?? 'unknown',
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
