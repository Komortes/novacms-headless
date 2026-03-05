<?php

namespace Tests\Unit\Services;

use App\Events\DomainEventBroadcasted;
use App\Services\DomainEventPublisher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class DomainEventPublisherTest extends TestCase
{
    public function test_it_broadcasts_domain_event_when_enabled(): void
    {
        Event::fake();
        config()->set('domain_events.broadcast.enabled', true);
        config()->set('domain_events.stream.enabled', false);

        $envelope = app(DomainEventPublisher::class)->publish('test.event', ['foo' => 'bar']);

        Event::assertDispatched(DomainEventBroadcasted::class, function (DomainEventBroadcasted $event) use ($envelope): bool {
            return $event->envelope['name'] === 'test.event'
                && $event->envelope['payload']['foo'] === 'bar'
                && $event->envelope['event_id'] === $envelope['event_id'];
        });
    }

    public function test_it_publishes_domain_event_to_redis_stream_when_enabled(): void
    {
        config()->set('domain_events.broadcast.enabled', false);
        config()->set('domain_events.stream.enabled', true);
        config()->set('domain_events.stream.name', 'novacms:test-stream');
        config()->set('domain_events.stream.maxlen', 500);

        Redis::shouldReceive('command')
            ->once()
            ->withArgs(function (string $command, array $arguments): bool {
                return $command === 'XADD'
                    && $arguments[0] === 'novacms:test-stream'
                    && $arguments[1] === '*'
                    && in_array('name', $arguments, true)
                    && in_array('test.event', $arguments, true);
            })
            ->andReturn('1710000000000-0');

        Redis::shouldReceive('command')
            ->once()
            ->withArgs(function (string $command, array $arguments): bool {
                return $command === 'XTRIM'
                    && $arguments[0] === 'novacms:test-stream';
            })
            ->andReturn(1);

        app(DomainEventPublisher::class)->publish('test.event', ['ok' => true]);

        $this->assertTrue(true);
    }
}
