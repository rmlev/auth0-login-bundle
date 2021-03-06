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
        'entity_users' => [
            'auth0_entity' => [
                'class' => 'Rmlev\Auth0LoginBundle\Tests\Entity\User',
                'property' => 'email',
            ],
        ],
    ],
    'firewalls' => [
        'stub' => [
            'provider' => 'auth0_in_memory',
            'auth0_login' => [
                'provider' => 'entity_users',
                'check_path' => 'stub_callback',
                'user_data_loader' => [
                    'default' => [
                        'identifier' => 'stub_id',
                        'map_options' => [],
                    ],
                ],
            ],
        ],
    ],
]);
