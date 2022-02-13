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
                'property' => 'email',
            ],
        ],
    ],
    'firewalls' => [
        'stub1' => [
            'pattern' => '^/stub1/',
            'provider' => 'auth0_in_memory',
            'auth0_login' => [
                'check_path' => '/stub1/login_check'
            ],
        ],
        'stub2' => [
            'provider' => 'entity_users',
            'auth0_login' => [
                'check_path' => 'stub_callback',
                'user_data_loader' => [
                    'default' => [
                        'identifier' => 'stub_id',
                    ],
                ],
            ],
        ],
    ],
]);
