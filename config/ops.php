<?php

return [
    'alerts' => [
        'queue_depth_threshold' => (int) env('OPS_ALERT_QUEUE_DEPTH_THRESHOLD', 25),
        'queue_lag_minutes_threshold' => (int) env('OPS_ALERT_QUEUE_LAG_MINUTES_THRESHOLD', 15),
        'failed_growth_per_hour_threshold' => (int) env('OPS_ALERT_FAILED_GROWTH_PER_HOUR_THRESHOLD', 3),
        'failed_absolute_per_hour_threshold' => (int) env('OPS_ALERT_FAILED_ABSOLUTE_PER_HOUR_THRESHOLD', 5),
    ],

    'health' => [
        'http_timeout_seconds' => (int) env('OPS_HEALTH_HTTP_TIMEOUT_SECONDS', 5),
        'socket_timeout_seconds' => (float) env('OPS_HEALTH_SOCKET_TIMEOUT_SECONDS', 2.0),
    ],
];
