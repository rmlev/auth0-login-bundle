<?php

$container->loadFromExtension('security', [
    'providers' => [
        'stub' => ['id' => 'foo'],
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
            'provider' => 'entity_users',
            'auth0' => [
                'check_path' => 'stub_callback',
                'user_data_loader' => [
                    'service' => 'stub_service',
                ],
            ],
        ],
    ],
]);