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
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0AbstractFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0AuthenticatorEntryPointFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0SecurityFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0UserProviderFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\SecurityNodeConfigurator;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0AuthenticatorFactory;
use Rmlev\Auth0LoginBundle\Tests\DependencyInjection\Security\Factory\Auth0AuthenticatorFactoryTest;
use Rmlev\Auth0LoginBundle\Tests\DependencyInjection\Security\Factory\Auth0AuthenticatorEntryPointFactoryTest;
use Rmlev\Auth0LoginBundle\Tests\DependencyInjection\Security\Factory\Auth0SecurityFactoryTest;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\EntryPointFactoryInterface;

abstract class BaseFactoryTestCase extends TestCase
{
    private ?Auth0AbstractFactory $factory = null;

    private array $classNameMap = [
        Auth0AuthenticatorFactoryTest::class => Auth0AuthenticatorFactory::class,
        Auth0SecurityFactoryTest::class => Auth0SecurityFactory::class,
        Auth0AuthenticatorEntryPointFactoryTest::class => Auth0AuthenticatorEntryPointFactory::class,
    ];

    public function configurationDataProvider(): \Generator
    {
        $options = $this->getOptions();

        yield [$this->getFactory(), $options, $this->getConfig($options)];
    }

    protected function createTest(Auth0AbstractFactory $factory, array $options, array $config): void
    {
        $container = new ContainerBuilder();

        $finalizedConfig = $this->getNode($factory, $config);
        $firewallName = 'fw';
        $defaultEntryPointId = 'entrypoint';

        [$providerId, $listenerId, $entryPointId] = $factory
            ->create($container, $firewallName, $finalizedConfig, 'user-provider', $defaultEntryPointId);

        $this->assertSame('security.authentication.provider.guard.auth0.'.$firewallName, $providerId);
        $this->assertSame('security.authentication.listener.guard.auth0.'.$firewallName, $listenerId);
        $this->assertSame($entryPointId, $defaultEntryPointId);
        $this->assertTrue($container->hasDefinition('rmlev_auth0_login.security.guard.authenticator.fw'));
    }

    protected function createEmptyEntryPointTest(Auth0AbstractFactory $factory, array $options, array $config): void
    {
        $container = new ContainerBuilder();

        $finalizedConfig = $this->getNode($factory, $config);
        $firewallName = 'fw';
        $defaultEntryPointId = null;

        [$providerId, $listenerId, $entryPointId] = $factory
            ->create($container, $firewallName, $finalizedConfig, 'user-provider', $defaultEntryPointId);

        $this->assertSame('security.authentication.provider.guard.auth0.'.$firewallName, $providerId);
        $this->assertSame('security.authentication.listener.guard.auth0.'.$firewallName, $listenerId);
        $this->assertSame($entryPointId, $this->getEntryPointId($firewallName));
        $this->assertTrue($container->hasDefinition('rmlev_auth0_login.security.guard.authenticator.fw'));
    }

    protected function createAuthenticatorTest(Auth0AbstractFactory $factory, array $options, array $config): void
    {
        $container = new ContainerBuilder();

        $finalizedConfig = $this->getNode($factory, $config);
        $firewallName = 'fw';

        $authenticatorId = $factory->createAuthenticator($container, $firewallName, $finalizedConfig, 'user-provider');

        $this->assertSame('rmlev_auth0_login.security.authenticator.'.$firewallName, $authenticatorId);
        $this->assertTrue($container->hasDefinition('rmlev_auth0_login.security.authenticator.'.$firewallName));
        $this->assertTrue($container->hasAlias($this->getEntryPointId($firewallName)));
    }

    protected function nodeDefinitionTest(Auth0AbstractFactory $factory, array $options, array $config): void
    {
        $finalizedConfig = $this->getNode($factory, $config);

        $this->assertSame($options, $finalizedConfig['user_data_loader']['default']['map_options']);
        $this->assertSame(
            $config['user_data_loader']['default']['identifier'],
            $finalizedConfig['user_data_loader']['default']['identifier']
        );
        $this->assertSame(
            $config['user_data_loader']['default']['auth0_key'],
            $finalizedConfig['user_data_loader']['default']['auth0_key']
        );
        $this->assertSame('callback', $finalizedConfig['check_path']);
    }

    protected function getFactory(): Auth0AbstractFactory
    {
        if ($this->factory === null) {
            if ($this->classNameMap[static::class] === Auth0AuthenticatorEntryPointFactory::class) {
                if (!interface_exists(EntryPointFactoryInterface::class)) {
                    return $this->createStub(Auth0AbstractFactory::class);
                }
            }

            $this->factory = new $this->classNameMap[static::class](
                new SecurityNodeConfigurator(),
                new Auth0UserProviderFactory()
            );
        }

        return $this->factory;
    }

    protected function getNode(Auth0AbstractFactory $factory, array $config): array
    {
        $nodeDefinition = new ArrayNodeDefinition('auth0-login');

        $factory->addConfiguration($nodeDefinition);
        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($config);

        return $node->finalize($normalizedConfig);
    }

    protected function getOptions(): array
    {
        return [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
    }

    protected function getConfig(array $options = null): array
    {
        $opt = $options ?? $this->getOptions();

        return [
            'provider' => 'foo',
            'user_data_loader' => [
                'default' => [
                    'identifier' => 'bar',
                    'auth0_key' => 'baz',
                    'map_options' => $opt
                ]
            ],
            'check_path' => 'callback',
        ];
    }

    protected function getEntryPointId(string $firewallName): string
    {
        return 'rmlev_auth0_login.entry_point.'.$firewallName;
    }
}
