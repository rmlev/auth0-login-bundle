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

namespace Rmlev\Auth0LoginBundle\Tests\DependencyInjection\Security\UserProvider;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\UserProvider\Auth0MemoryFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class Auth0MemoryFactoryTest extends TestCase
{
    /**
     * @dataProvider configurationDataProvider
     */
    public function testAddConfiguration(array $config, bool $valid): void
    {
        if (!$valid) {
            $this->expectException(InvalidConfigurationException::class);
        }
        $nodeDefinition = new ArrayNodeDefinition('auth0-memory');

        $factory = new Auth0MemoryFactory();

        $factory->addConfiguration($nodeDefinition);
        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($config);

        $finalized = $node->finalize($normalizedConfig);

        $mergedConfig = array_merge_recursive(
            [
                'user_data_loader' => [
                    'default' => [
                        'map_options' => []
                    ]
                ]
            ],
            $config
        );
        $this->assertEquals($mergedConfig, $finalized);
    }

    public function configurationDataProvider(): \Generator
    {
        yield [
            [
                'user_data_loader' => [
                    'default' => [
                        'identifier' => 'bar',
                        'auth0_key' => 'baz',
                        'map_options' => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                            'key3' => 'value3',
                            'key4' => 'value4',
                        ]
                    ]
                ]
            ],
            true
        ];

        yield [
            [
                'user_data_loader' => [
                    'default' => [
                        'identifier' => 'stub',
                    ]
                ]
            ],
            true
        ];

        yield [
            [
                'user_data_loader' => [
                    'wrong' => 'foo',
                    'default' => [
                        'identifier' => 'bar',
                        'auth0_key' => 'baz',
                        'map_options' => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                            'key3' => 'value3',
                            'key4' => 'value4',
                        ]
                    ]
                ]
            ],
            false
        ];
    }
}
