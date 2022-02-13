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
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\EntryPointFactoryInterface;

final class Auth0AuthenticatorEntryPointFactoryTest extends BaseFactoryTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (!interface_exists(EntryPointFactoryInterface::class)) {
            self::markTestSkipped('Interface '.EntryPointFactoryInterface::class.' not found.');
        }
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
    public function testCreateAuthenticator(Auth0AbstractFactory $factory, array $options, array $config): void
    {
        $this->createAuthenticatorTest($factory, $options, $config);
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testCreate(Auth0AbstractFactory $factory, array $options, array $config): void
    {
        $this->createTest($factory, $options, $config);
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testCreateEmptyEntryPoint(Auth0AbstractFactory $factory, array $options, array $config): void
    {
        if (!interface_exists(EntryPointFactoryInterface::class)) {
            $this->markTestSkipped('Interface '.EntryPointFactoryInterface::class.' not found.');
        }

        $this->createEmptyEntryPointTest($factory, $options, $config);
    }
}
