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

use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0AbstractFactory;

final class Auth0SecurityFactoryTest extends BaseFactoryTestCase
{
    /**
     * @dataProvider configurationDataProvider
     */
    public function testGetPosition(Auth0AbstractFactory $factory): void
    {
        $this->assertSame('http', $factory->getPosition());
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testNodeDefinition(Auth0AbstractFactory $factory, array $options, array $config): void
    {
        $this->nodeDefinitionTest($factory, $options, $config);
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testCreate(Auth0AbstractFactory $factory, array $options, array $config): void
    {
        $this->createTest($factory,$options, $config);
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testCreateEmptyEntryPoint(Auth0AbstractFactory $factory, array $options, array $config): void
    {
        $this->createEmptyEntryPointTest($factory, $options, $config);
    }
}
