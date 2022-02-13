<?php

$container->loadFromExtension('security', [
    'providers' => [
        'auth0_in_memory' => [
            'auth0_memory' => [
                'user_data_loader' => [
                    'default' => [
                        'identifier' => 'email',
                        'auth0_key' => 'sub',
                        'map_options' => [
                            'sub' => 'auth0_user_key',
                            'picture' => 'avatar',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'firewalls' => [
        'stub' => [
            'anonymous' => true,
            'provider' => 'auth0_in_memory',
//            'auth0_login' => [
//                'check_path' => 'stub_callback',
//            ],
        ],
    ],
]);
