<?php

declare(strict_types=1);

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\DependencyInjection\RmlevAuth0LoginExtension;
use Rmlev\Auth0LoginBundle\RmlevAuth0LoginBundle;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class Auth0SecurityExtensionTest extends TestCase
{
    public function testInvalidConfiguration(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("You should configure only one of: 'service', 'default'.");
        $container = $this->buildContainer();

        if ($this->isNewSecuritySystem()) {
            $config = [
                'enable_authenticator_manager' => true,

                'firewalls' => [
                    'some_firewall' => [
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'service' => 'foo',
                                'default' => []
                            ],
                        ]
                    ],
                ],
            ];
        } else {
            $config = [
                'firewalls' => [
                    'some_firewall' => [
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'service' => 'foo',
                                'default' => []
                            ],
                        ]
                    ],
                ],
            ];
        }
        $container->loadFromExtension('security', $config);

        $container->compile();
    }

    public function testCorrectConfiguration(): void
    {
        $container = $this->buildContainer();

        if ($this->isNewSecuritySystem()) {
            $config = [
                'enable_authenticator_manager' => true,
                'providers' => [
                    'default' => ['id' => 'rmlev_auth0_login.security.user.provider'],
                    'auth0_in_memory' => [
                        'auth0_memory' => [
                            'user_data_loader' => []
                        ]
                    ],
                    'entity_users' => [
                        'auth0_entity' => [
                            'class' => 'TestEntity',
                            'property' => 'key',
                        ]
                    ]
                ],
                'firewalls' => [
                    'some_firewall' => [
                        'provider' => 'auth0_in_memory',
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'service' => 'foo',
                            ],
                        ]
                    ],
                ],
            ];
        } else {
            $config = [
                'providers' => [
                    'default' => ['id' => 'rmlev_auth0_login.security.user.provider'],
                    'auth0_in_memory' => [
                        'auth0_memory' => [
                            'user_data_loader' => []
                        ]
                    ],
                    'entity_users' => [
                        'auth0_entity' => [
                            'class' => 'TestEntity',
                            'property' => 'key',
                        ]
                    ]
                ],
                'firewalls' => [
                    'some_firewall' => [
                        'provider' => 'auth0_in_memory',
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'service' => 'foo',
                            ],
                        ]
                    ],
                ],
            ];
        }
        $container->loadFromExtension('security', $config);

        $container->compile();

        $this->assertTrue($container->hasDefinition('rmlev_auth0_login.security.authenticator'));
    }

    public function testUserDataLoaderMap(): void
    {
        $container = $this->buildContainer();

        $options = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => 'value4',
            'key5' => 'value5',
        ];
        if ($this->isNewSecuritySystem()) {
            $config = [
                'enable_authenticator_manager' => true,
                'providers' => [
                    'auth0_in_memory' => [
                        'auth0_memory' => [
                            'user_data_loader' => []
                        ]
                    ],
                ],
                'firewalls' => [
                    'some_firewall' => [
                        'provider' => 'auth0_in_memory',
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'default' => [
                                    'map_options' => $options
                                ],
                            ],
                        ]
                    ],
                ],
            ];
        } else {
            $config = [
                'providers' => [
                    'auth0_in_memory' => [
                        'auth0_memory' => [
                            'user_data_loader' => []
                        ]
                    ],
                ],
                'firewalls' => [
                    'some_firewall' => [
                        'provider' => 'auth0_in_memory',
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'default' => [
                                    'map_options' => $options
                                ],
                            ],
                        ]
                    ],
                ],
            ];
        }
        $container->loadFromExtension('security', $config);

        $container->compile();

        $userDataLoader = $container->getDefinition('rmlev_auth0_login.helper_auth0response.response_user_data_loader.some_firewall');
        $this->assertSame($options, $userDataLoader->getArgument('$mapOptions'));
    }

    public function testUserDataLoaderMapProvider(): void
    {
        $container = $this->buildContainer();

        $options = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => 'value4',
            'key5' => 'value5',
        ];
        if ($this->isNewSecuritySystem()) {
            $config = [
                'enable_authenticator_manager' => true,
                'providers' => [
                    'auth0_in_memory' => [
                        'auth0_memory' => [
                            'user_data_loader' => [
                                'default' => [
                                    'map_options' => $options
                                ],
                            ]
                        ]
                    ],
                ],
                'firewalls' => [
                    'some_firewall' => [
                        'provider' => 'auth0_in_memory',
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [],
                        ]
                    ],
                ],
            ];
        } else {
            $config = [
                'providers' => [
                    'auth0_in_memory' => [
                        'auth0_memory' => [
                            'user_data_loader' => [
                                'default' => [
                                    'map_options' => $options
                                ],
                            ]
                        ]
                    ],
                ],
                'firewalls' => [
                    'some_firewall' => [
                        'provider' => 'auth0_in_memory',
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [],
                        ]
                    ],
                ],
            ];
        }
        $container->loadFromExtension('security', $config);

        $container->compile();

        $userDataLoader = $container->getDefinition('concrete.rmlev_auth0_login.helper_auth0response.response_user_data_loader');
        $this->assertSame($options, $userDataLoader->getArgument('$mapOptions'));
    }

    public function testUserDataLoaderMapOverride(): void
    {
        $container = $this->buildContainer();

        $options = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => 'value4',
            'key5' => 'value5',
        ];
        if ($this->isNewSecuritySystem()) {
            $config = [
                'enable_authenticator_manager' => true,
                'providers' => [
                    'auth0_in_memory' => [
                        'auth0_memory' => [
                            'user_data_loader' => [
                                'default' => [
                                    'map_options' => $options
                                ],
                            ]
                        ]
                    ],
                ],
                'firewalls' => [
                    'some_firewall' => [
                        'provider' => 'auth0_in_memory',
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'default' => []
                            ],
                        ]
                    ],
                ],
            ];
        } else {
            $config = [
                'providers' => [
                    'auth0_in_memory' => [
                        'auth0_memory' => [
                            'user_data_loader' => [
                                'default' => [
                                    'map_options' => $options
                                ],
                            ]
                        ]
                    ],
                ],
                'firewalls' => [
                    'some_firewall' => [
                        'provider' => 'auth0_in_memory',
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'default' => []
                            ],
                        ]
                    ],
                ],
            ];
        }
        $container->loadFromExtension('security', $config);

        $container->compile();

        $userDataLoader = $container->getDefinition('rmlev_auth0_login.helper_auth0response.response_user_data_loader.some_firewall');
        $this->assertSame([], $userDataLoader->getArgument('$mapOptions'));
    }

    private function buildContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.project_dir' => 'stub',
            'kernel.container_class' => 'stub',
        ]));

        $framework = new FrameworkExtension();
        $container->registerExtension($framework);
        $container->loadFromExtension('framework', [
            'cache' => [
                'system' => 'cache.adapter.array'
            ],
        ]);

        $security = new SecurityExtension();
        $container->registerExtension($security);

        $doctrine = new DoctrineExtension();
        $container->registerExtension($doctrine);

        $auth0 = new RmlevAuth0LoginExtension();
        $container->registerExtension($auth0);
        $container->loadFromExtension(
            'rmlev_auth0_login',
            [
                'domain' => 'test.domain',
                'client_id' => 'test.client_id',
                'client_secret' => 'test.client_secret',
                'cookie_secret' => 'test.cookie_secret'
            ],
        );

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);

        $frameworkBundle = new FrameworkBundle();
        $frameworkBundle->build($container);

        $securityBundle = new SecurityBundle();
        $securityBundle->build($container);

        $auth0Bundle = new RmlevAuth0LoginBundle();
        $auth0Bundle->build($container);

        return $container;
    }

    private function isNewSecuritySystem(): bool
    {
        return interface_exists(AuthenticatorFactoryInterface::class);
    }
}
