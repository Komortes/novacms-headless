<?php

return [
    'graphql' => [
        'route_per_minute' => (int) env('GRAPHQL_ROUTE_RATE_LIMIT_PER_MINUTE', 120),
        'read_per_minute' => (int) env('GRAPHQL_READ_RATE_LIMIT_PER_MINUTE', 120),
        'search_per_minute' => (int) env('GRAPHQL_SEARCH_RATE_LIMIT_PER_MINUTE', 30),
        'write_per_minute' => (int) env('GRAPHQL_WRITE_RATE_LIMIT_PER_MINUTE', 20),
    ],
];
