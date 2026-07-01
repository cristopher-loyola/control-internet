<?php

return [
    'paths'                    => ['api/*'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => [
        'http://localhost:5173',
        'http://localhost:4173',
        'https://controlinternetpagos.uk',
        'https://pagos.controlinternetpagos.uk',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => false,
];
