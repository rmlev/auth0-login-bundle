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

namespace Rmlev\Auth0LoginBundle\Tests\DependencyInjection\Security\Factory;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\SecurityNodeConfigurator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class SecurityNodeConfiguratorTest extends TestCase
{
    /**
     * @dataProvider getConfig
     */
    public function testAddConfiguration(array $config): void
    {
        $nodeDefinition = new ArrayNodeDefinition('auth0-login');

        $nodeConfigurator = new SecurityNodeConfigurator();

        $nodeConfigurator->addConfiguration($nodeDefinition);
        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($config);

        $finalizedConfig = $node->finalize($normalizedConfig);

        $this->assertSame(
            $this->getOptions(),
            $finalizedConfig['user_data_loader']['default']['map_options']
        );
    }

    /**
     * @dataProvider getInvalidConfig
     */
    public function testAddInvalidConfiguration(array $config): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $nodeDefinition = new ArrayNodeDefinition('auth0-login');

        $nodeConfigurator = new SecurityNodeConfigurator();
        $nodeConfigurator->addConfiguration($nodeDefinition);
        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($config);
        $node->finalize($normalizedConfig);
    }

    public function getConfig(): \Generator
    {
        $options = $this->getOptions();

        yield [
            [
                'user_data_loader' => [
                    'default' => [
                        'identifier' => 'bar',
                        'auth0_key' => 'baz',
                        'map_options' => $options
                    ]
                ],
                'check_path' => 'callback',
            ]
        ];

        yield [
            [
                'user_data_loader' => [
                    'default' => [
                        'map_options' => $options
                    ]
                ],
                'check_path' => 'callback',
            ]
        ];
    }

    public function getInvalidConfig(): \Generator
    {
        $options = $this->getOptions();

        yield [
            [
                'user_data_loader' => [
                    'default' => [
                        'identifier' => 'bar',
                        'auth0_key' => 'baz',
                        'map_options' => $options
                    ]
                ],
                'check_path' => 'callback',
                'wrong' => 'foo',
            ]
        ];

        yield [
            [
                'user_data_load' => [
                    'default' => [
                        'identifier' => 'bar',
                        'auth0_key' => 'baz',
                        'map_options' => $options
                    ]
                ],
                'check_path' => 'callback',
            ]
        ];

        yield [
            [
                'user_data_loader' => [
                    'default' => [
                        'identifier' => 'bar',
                        'auth0_key' => 'baz',
                        'map_options' => [
                            ['key1' => 'value1']
                        ]
                    ]
                ],
                'check_path' => 'callback',
            ]
        ];

        yield [
            [
                'user_data_loader' => [
                    'default' => [
                        'identifier' => 'bar',
                        'auth0_key' => 'baz',
                        'map_options' => [
                            'key1' => 'value1',
                            ['key2' => 'value2']
                        ]
                    ]
                ],
                'check_path' => 'callback',
            ]
        ];
    }

    private function getOptions(): array
    {
        return [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => 'value4',
        ];
    }
}
