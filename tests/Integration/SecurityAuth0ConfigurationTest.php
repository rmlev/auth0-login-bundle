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

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0AuthenticatorEntryPointFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0AuthenticatorFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0SecurityFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0UserProviderFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\SecurityNodeConfigurator;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\UserProvider\Auth0MemoryFactory;
use Symfony\Bridge\Doctrine\DependencyInjection\Security\UserProvider\EntityFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\MainConfiguration;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\EntryPointFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class SecurityAuth0ConfigurationTest extends TestCase
{
    /**
     * The minimal, required config needed to not have any required validation
     * issues.
     */
    protected static array $minimalConfig = [
        'providers' => [
            'stub' => [
                'id' => 'foo',
            ],
        ],
        'firewalls' => [
            'stub' => [],
        ],
    ];

    /**
     * @dataProvider getConfig
     */
    public function testConfig(array $config, $expectedLoaderConfig, bool $ok): void
    {
        if (!$ok) {
            $this->expectException(InvalidConfigurationException::class);
        }

        $configMerged = array_merge(self::$minimalConfig, $config);

        $processor = new Processor();

        $factories = [];
        if (method_exists(SecurityExtension::class, 'addAuthenticatorFactory')) {
            $auth0Factory = new Auth0AuthenticatorFactory(
                new SecurityNodeConfigurator(),
                new Auth0UserProviderFactory()
            );
            $factories['stub'] = $auth0Factory;
        } elseif (interface_exists(AuthenticatorFactoryInterface::class)) {
            if (!interface_exists(EntryPointFactoryInterface::class)) {
                $auth0Factory = new Auth0AuthenticatorFactory(
                    new SecurityNodeConfigurator(),
                    new Auth0UserProviderFactory()
                );
            } else {
                // for Symfony 5.1
                $auth0Factory = new Auth0AuthenticatorEntryPointFactory(
                    new SecurityNodeConfigurator(),
                    new Auth0UserProviderFactory()
                );
            }
            $factories['stub'] = [$auth0Factory];
        } else {
            $auth0Factory = new Auth0SecurityFactory(
                new SecurityNodeConfigurator(),
                new Auth0UserProviderFactory()
            );
            $factories['stub'] = [$auth0Factory];
        }

        $memoryProviderFactory = new Auth0MemoryFactory();
        $entityProviderFactory = new EntityFactory(
            'auth0-entity',
            'stub_entity_user_provider'
        );

        $configuration = new MainConfiguration(
            $factories,
            [$memoryProviderFactory, $entityProviderFactory]
        );
        $processedConfig = $processor->processConfiguration($configuration, [$configMerged]);

        $this->assertIsArray($processedConfig['firewalls']['stub']['auth0_login']);
        if ($expectedLoaderConfig !== null) {
            $this->assertSame(
                $expectedLoaderConfig,
                $processedConfig['firewalls']['stub']['auth0_login']['user_data_loader']
            );
        } else {
            $this->assertArrayNotHasKey('user_data_loader', $processedConfig['firewalls']['stub']['auth0_login']);
        }
    }

    public function getConfig(): \Generator
    {
        $mapOptions = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => 'value4',
            'key5' => 'value5',
        ];

        // Valid configs
        yield [
            [
                'providers' => [
                    'auth0_in_memory' => [
                        'auth0_memory' => [],
                    ],
                ],
                'firewalls' => [
                    'stub' => [
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'default' => []
                            ]
                        ],
                    ],
                ],
            ],
            [
                'default' => ['map_options' => []]
            ],
            true
        ];

        yield [
            [
                'providers' => [
                    'auth0_in_memory' => [
                        'auth0_memory' => [],
                    ],
                ],
                'firewalls' => [
                    'stub' => [
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'default' => [
                                    'identifier' => 'stub_id',
                                    'auth0_key' => 'stub_key',
                                ]
                            ]
                        ],
                    ],
                ],
            ],
            [
                'default' => [
                    'identifier' => 'stub_id',
                    'auth0_key' => 'stub_key',
                    'map_options' => [],
                ]
            ],
            true
        ];

        yield [
            [
                'providers' => [
                    'auth0_in_memory' => [
                        'auth0_memory' => [],
                    ],
                ],
                'firewalls' => [
                    'stub' => [
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'default' => [
                                    'identifier' => 'stub_id',
                                    'auth0_key' => 'stub_key',
                                    'map_options' => $mapOptions,
                                ]
                            ]
                        ],
                    ],
                ],
            ],
            [
                'default' => [
                    'identifier' => 'stub_id',
                    'auth0_key' => 'stub_key',
                    'map_options' => $mapOptions,
                ]
            ],
            true
        ];

        yield [
            [
                'providers' => [
                    'auth0_memory_stub' => [
                        'auth0_memory' => [],
                    ],
                    'entity_stub' => [
                        'auth0_entity' => [
                            'class' => 'ClassName',
                            'property' => 'stub_property',
                        ],
                    ],
                ],
                'firewalls' => [
                    'stub' => [
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                            'user_data_loader' => [
                                'default' => [
                                    'identifier' => 'stub_id',
                                    'auth0_key' => 'stub_key',
                                    'map_options' => $mapOptions,
                                ]
                            ]
                        ],
                    ],
                ],
            ],
            [
                'default' => [
                    'identifier' => 'stub_id',
                    'auth0_key' => 'stub_key',
                    'map_options' => $mapOptions,
                ]
            ],
            true
        ];

        yield [
            [
                'providers' => [
                    'auth0_in_memory' => [
                        'auth0_memory' => [],
                    ],
                ],
                'firewalls' => [
                    'stub' => [
                        'auth0_login' => [
                            'check_path' => 'stub_callback',
                        ],
                    ],
                ],
            ],
            null,
            true
        ];

        // Invalid configs
        yield [
            [
                'providers' => [
                    'auth0_in_memory' => [
                        'auth0_memory' => [],
                    ],
                ],
                'firewalls' => [
                    'stub' => [
                        'auth0_login' => [
                            'wrong' => 'foo'
                        ],
                    ],
                ],
            ],
            null,
            false
        ];

        yield [
            [
                'providers' => [
                    'stub_memory' => [
                        'auth0_in_memory' => [],
                    ],
                ],
                'firewalls' => [
                    'stub' => [
                        'auth0_login' => [
                        ],
                    ],
                ],
            ],
            null,
            false
        ];
    }
}
