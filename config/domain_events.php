<?php

return [
    'broadcast' => [
        'enabled' => (bool) env('DOMAIN_EVENTS_BROADCAST_ENABLED', true),
        'channel' => env('DOMAIN_EVENTS_BROADCAST_CHANNEL', 'novacms.domain-events'),
    ],

    'stream' => [
        'enabled' => (bool) env('DOMAIN_EVENTS_STREAM_ENABLED', true),
        'name' => env('DOMAIN_EVENTS_STREAM_NAME', 'novacms:domain-events'),
        'maxlen' => (int) env('DOMAIN_EVENTS_STREAM_MAXLEN', 10000),
    ],
];
