<?php

$container->loadFromExtension('security', [
    'enable_authenticator_manager' => true,
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
            ],
        ],
    ],
    'firewalls' => [
        'stub' => [
            'provider' => 'entity_users',
            'auth0_login' => [
                'provider' => 'entity_users',
                'check_path' => 'stub_callback',
                'user_data_loader' => [
                    'default' => [
                        'map_options' => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                            'key3' => 'value3',
                        ],
                    ],
                ],
            ],
        ],
    ],
]);
