<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Nuwave\Lighthouse\Http\Middleware\AcceptJson;
use Nuwave\Lighthouse\Http\Middleware\AttemptAuthentication;

return [
    'route' => [
        'uri' => '/graphql',
        'name' => 'graphql',
        'middleware' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            SubstituteBindings::class,
            AcceptJson::class,
            AttemptAuthentication::class,
            'throttle:graphql-route',
        ],
    ],

    'guards' => ['web', 'api-token'],

    'security' => [
        'max_query_complexity' => (int) env('LIGHTHOUSE_SECURITY_MAX_QUERY_COMPLEXITY', 80),
        'max_query_depth' => (int) env('LIGHTHOUSE_SECURITY_MAX_QUERY_DEPTH', 8),
        'disable_introspection' => (bool) env('LIGHTHOUSE_SECURITY_DISABLE_INTROSPECTION', false)
            ? GraphQL\Validator\Rules\DisableIntrospection::ENABLED
            : GraphQL\Validator\Rules\DisableIntrospection::DISABLED,
    ],
];
