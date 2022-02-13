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
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0AuthenticatorEntryPointFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0AuthenticatorFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0SecurityFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0UserProviderFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\EntryPointFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class Auth0UserProviderFactoryTest extends BaseFactoryTestCase
{
    public function testCreateAuth0UserProvider(): void
    {
        $factory = $this->createAuthenticatorFactory();

        $config = $this->getConfig();

        $userProviderFactory = new Auth0UserProviderFactory();
        $container = new ContainerBuilder();
        $firewallName = 'firewall1';
        $userProviderId = 'user-provider';

        $finalizedConfig = $this->getNode($factory, $config);

        $userProviderReference = $userProviderFactory->createAuth0UserProvider($container, $firewallName, $finalizedConfig, $userProviderId);

        $this->assertInstanceOf(Reference::class, $userProviderReference);
        $this->assertEquals($userProviderId.'.'.$firewallName, $userProviderReference);
    }

    public function testCreateAuth0UserProviderEmpty(): void
    {
        $factory = $this->createAuthenticatorFactory();

        $config = $this->getConfig();

        $userProviderFactory = new Auth0UserProviderFactory();
        $container = new ContainerBuilder();
        $firewallName = 'firewall1';

        $finalizedConfig = $this->getNode($factory, $config);

        $userProviderReference = $userProviderFactory->createAuth0UserProvider($container, $firewallName, $finalizedConfig, null);

        $this->assertInstanceOf(Reference::class, $userProviderReference);
        $this->assertEquals(Auth0UserProviderFactory::BASE_USER_PROVIDER_ID.'.'.$firewallName, $userProviderReference);
    }

    private function createAuthenticatorFactory(): Auth0AbstractFactory
    {
        if (method_exists(SecurityExtension::class, 'addAuthenticatorFactory')) {
            $factory = new Auth0AuthenticatorFactory();
        } elseif (interface_exists(AuthenticatorFactoryInterface::class)) {
            if (!interface_exists(EntryPointFactoryInterface::class)) {
                $factory = new Auth0AuthenticatorFactory();
            } else {
                // for Symfony 5.1
                $factory = new Auth0AuthenticatorEntryPointFactory();
            }
        } else {
            $factory = new Auth0SecurityFactory();
        }

        return $factory;
    }
}
