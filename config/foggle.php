<?php

return [
    'default' => env('FOGGLE_STORE', 'array'),

    'stores' => [

        'array' => [
            'driver' => 'array',
        ],

        'redis' => [
            'driver'     => 'redis',
            'connection' => 'default',
        ],

    ],

    'context_resolvers' => [
        \Illuminate\Foundation\Auth\User::class => \YouCanShop\Foggle\ContextResolvers\UserContextResolver::class,
    ],
];
