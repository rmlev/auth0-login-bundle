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
use Rmlev\Auth0LoginBundle\DependencyInjection\Compiler\OptionsCompilerPass;
use Rmlev\Auth0LoginBundle\DependencyInjection\RmlevAuth0LoginExtension;
use Rmlev\Auth0LoginBundle\RmlevAuth0LoginBundle;
use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0UserProvider;
use Rmlev\Auth0LoginBundle\Security\Core\User\EntityUserProvider;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

abstract class BaseAuth0SecurityExtensionTest extends TestCase
{
    /**
     * @dataProvider providerInvalidConfigurations
     */
    public function testInvalidConfiguration(string $configuration): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->compileContainer($configuration, false);
    }

    public function providerInvalidConfigurations(): \Generator
    {
        yield [
            'security',
        ];

        yield [
            'security1',
        ];

        yield [
            'security2',
        ];

        yield [
            'security3',
        ];

        yield [
            'security4',
        ];

        yield [
            'security5',
        ];

        yield [
            'security6',
        ];

        yield [
            'security7',
        ];

//        yield [
//            'security8',
//        ];

        yield [
            'security9',
        ];

        yield [
            'security10',
        ];

        yield [
            'security_multiple_firewalls',
        ];
    }

    /**
     * @dataProvider providerConfigurations
     */
    public function testValidConfiguration(string $configuration, array $firewalls, bool $newSecuritySystem, bool $auth0Enabled): void
    {
        $this->skipGuardIntegrationTest($configuration);
        $container = $this->compileContainer($configuration);

        if ($this->isNewSecuritySystem() === false) {
            $newSecuritySystem = false;
        }

        foreach ($firewalls as $firewallName) {
            if ($newSecuritySystem) {
                $this->assertTrue($container->hasDefinition('rmlev_auth0_login.security.authenticator'));
                if ($auth0Enabled) {
                    $this->assertTrue($container->hasDefinition('rmlev_auth0_login.security.authenticator.'.$firewallName));
                }
            } else {
                $this->assertTrue($container->hasDefinition('rmlev_auth0_login.security.guard.authenticator'));
                if ($auth0Enabled) {
                    $this->assertTrue($container->hasDefinition('rmlev_auth0_login.security.guard.authenticator.'.$firewallName));
                    $this->assertTrue($container->hasDefinition('security.authentication.provider.guard.auth0.'.$firewallName));
                }
            }
        }
    }

    public function providerConfigurations(): \Generator
    {
        yield [
            'security',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security_full',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security_full_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security1',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security1_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security2',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security2_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security3',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security3_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security4',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security4_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security5',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security5_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security6',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security6_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security7',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security7_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security8',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security8_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security9',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security9_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security10',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security10_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security11',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security11_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security12',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security12_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security13',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security13_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security14',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security14_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security15',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security15_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security16',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security16_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security17',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security17_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security18',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security18_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security19',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security19_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security20',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security20_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security21',
            ['stub'],
            true,
            true,
        ];

        yield [
            'security21_guard',
            ['stub'],
            false,
            true,
        ];

        yield [
            'security22_guard',
            ['stub'],
            false,
            false,
        ];

        yield [
            'security_multiple_firewalls',
            ['stub1', 'stub2'],
            true,
            false,
        ];

        yield [
            'security_multiple_firewalls_guard',
            ['stub1', 'stub2'],
            true,
            false,
        ];
    }

    /**
     * @dataProvider providerTestUserProvider
     */
    public function testUserProvider(string $configuration, string $userProviderClass, array $firewalls, bool $newSecuritySystem): void
    {
        $this->skipGuardIntegrationTest($configuration);
        $container = $this->compileContainer($configuration);

        if ($this->isNewSecuritySystem() === false) {
            $newSecuritySystem = false;
        }

        foreach ($firewalls as $firewallName) {
            if ($newSecuritySystem) {
                $authenticatorDefinition = $container->getDefinition('rmlev_auth0_login.security.authenticator.'.$firewallName);
            } else {
                $authenticatorDefinition = $container->getDefinition('rmlev_auth0_login.security.guard.authenticator.'.$firewallName);
            }

            $userProviderReference = (string) $authenticatorDefinition->getArgument('$userProvider');
            $userProviderDefinition = $container->getDefinition($userProviderReference);

            $this->assertSame($userProviderClass, $userProviderDefinition->getClass());
        }
    }

    public function providerTestUserProvider(): \Generator
    {
        yield [
            'security',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security_full',
            EntityUserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security_full_guard',
            EntityUserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security1',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security1_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security2',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security2_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security3',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security3_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security4',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security4_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security5',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security5_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security6',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security6_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security7',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security7_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security8',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security8_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security9',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security9_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security10',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security10_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security11',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security11_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security12',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security12_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security13',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security13_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security14',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security14_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security15',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security15_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security16',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security16_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security17',
            EntityUserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security17_guard',
            EntityUserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security18',
            EntityUserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security18_guard',
            EntityUserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security19',
            EntityUserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security19_guard',
            EntityUserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security20',
            EntityUserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security20_guard',
            EntityUserProvider::class,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security21',
            Auth0UserProvider::class,
            ['stub'],
            true,
            true,
        ];

        yield [
            'security21_guard',
            Auth0UserProvider::class,
            ['stub'],
            false,
            true,
        ];
    }

    /**
     * @dataProvider providerDataLoaderOptions
     */
    public function testDataLoaderOptions(string $configuration, ?string $identifier, ?string $auth0Key, array $mapOptions, ?string $service, array $firewalls, bool $newSecuritySystem): void
    {
        $this->skipGuardIntegrationTest($configuration);
        $container = $this->buildContainer();
        $this->loadFromFile($container, $configuration);
        $container->compile();

        if ($this->isNewSecuritySystem() === false) {
            $newSecuritySystem = false;
        }

        foreach ($firewalls as $firewallName) {
            if ($newSecuritySystem) {
                $authenticatorDefinition = $container->getDefinition('rmlev_auth0_login.security.authenticator.'.$firewallName);
            } else {
                $authenticatorDefinition = $container->getDefinition('rmlev_auth0_login.security.guard.authenticator.'.$firewallName);
            }

            $userProviderReference = (string) $authenticatorDefinition->getArgument('$userProvider');
            $userProviderDefinition = $container->getDefinition($userProviderReference);
            $dataLoaderReference = (string) $userProviderDefinition->getArgument('$responseUserDataLoader');

            if ($service !== null) {
                $this->assertSame($service, $dataLoaderReference);
            } else {
                $dataLoaderDefinition = $container->getDefinition($dataLoaderReference);

                $this->assertSame($identifier, $dataLoaderDefinition->getArgument('$identifier'));
                $this->assertSame($auth0Key, $dataLoaderDefinition->getArgument('$auth0Key'));
                $this->assertSame($mapOptions, $dataLoaderDefinition->getArgument('$mapOptions'));
            }
        }
    }

    public function providerDataLoaderOptions(): \Generator
    {
        yield [
            'security',
            '',
            '',
            [],
            'stub_service',
            ['stub'],
            true,

        ];

        yield [
            'security1',
            'stub_id',
            'stub_key',
            [
                'sub' => 'auth0_user_key',
                'picture' => 'avatar'
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security1_guard',
            'stub_id',
            'stub_key',
            [
                'sub' => 'auth0_user_key',
                'picture' => 'avatar'
            ],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security2',
            'email',
            'sub',
            [
                'sub' => 'auth0_user_key',
                'picture' => 'avatar'
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security2_guard',
            'email',
            'sub',
            [
                'sub' => 'auth0_user_key',
                'picture' => 'avatar'
            ],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security3',
            '',
            '',
            [],
            'stub_loader',
            ['stub'],
            true,
        ];

        yield [
            'security3_guard',
            '',
            '',
            [],
            'stub_loader',
            ['stub'],
            false,
        ];

        yield [
            'security4',
            null,
            null,
            [],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security4_guard',
            null,
            null,
            [],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security5',
            'stub_id',
            null,
            [],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security5_guard',
            'stub_id',
            null,
            [],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security6',
            'stub_id',
            'stub_key',
            [],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security6_guard',
            'stub_id',
            'stub_key',
            [],
            null,
            ['stub'],
            false,
            true,
        ];

        yield [
            'security7',
            null,
            'stub_key',
            [],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security7_guard',
            null,
            'stub_key',
            [],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security8',
            'stub_id',
            'stub_key',
            [],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security8_guard',
            'stub_id',
            'stub_key',
            [],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security9',
            'stub_id',
            'stub_key',
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security9_guard',
            'stub_id',
            'stub_key',
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security10',
            'stub_id',
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security10_guard',
            'stub_id',
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security11',
            null,
            'stub_key',
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security11_guard',
            null,
            'stub_key',
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security12',
            null,
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security12_guard',
            null,
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security13',
            null,
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security13_guard',
            null,
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security14',
            null,
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security14_guard',
            null,
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security15',
            null,
            null,
            [
                'key1' => 'stub_value1',
                'key2' => 'stub_value2',
                'key3' => 'stub_value3',
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security15_guard',
            null,
            null,
            [
                'key1' => 'stub_value1',
                'key2' => 'stub_value2',
                'key3' => 'stub_value3',
            ],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security16',
            null,
            null,
            [
                'key1' => 'stub_value1',
                'key3' => 'stub_value3',
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security16_guard',
            null,
            null,
            [
                'key1' => 'stub_value1',
                'key3' => 'stub_value3',
            ],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security17',
            'stub_id',
            null,
            [],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security17_guard',
            'stub_id',
            null,
            [],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security18',
            'stub_id',
            null,
            [],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security18_guard',
            'stub_id',
            null,
            [],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security19',
            null,
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security19_guard',
            null,
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            false,
        ];

        yield [
            'security20',
            null,
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            true,
        ];

        yield [
            'security20_guard',
            null,
            null,
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            null,
            ['stub'],
            false,
        ];
    }

    /**
     * @dataProvider providerHelperOptions
     */
    public function testHelperOptions(string $configuration, array $options): void
    {
        $this->skipGuardIntegrationTest($configuration);
        $container = $this->buildContainer();
        $this->loadFromFile($container, $configuration);
        $container->compile();

        $helperDefinition = $container->getDefinition('rmlev_auth0_login.helper.auth0helper');
        $optionsCollectionReference = (string) $helperDefinition->getArgument(3);
        $this->assertSame('rmlev_auth0_login.helper.auth0options_collection', $optionsCollectionReference);
        $optionsCollectionDefinition = $container->getDefinition($optionsCollectionReference);

        $this->assertSame(1, count($optionsCollectionDefinition->getMethodCalls()));
        $this->assertEquals(
            [
                'addOptions',
                [new Reference('rmlev_auth0_login.helper.auth0options.stub'), 'stub']
            ],
            $optionsCollectionDefinition->getMethodCalls()[0]
        );
        $optionsDefinition = $container->getDefinition('rmlev_auth0_login.helper.auth0options.stub');
        $this->assertEquals($options, $optionsDefinition->getArgument(0));
    }

    public function providerHelperOptions(): \Generator
    {
        $options = [
            'check_path' => '/login_check',
            'use_forward' => false,
            'require_previous_session' => false,
            'login_path' => '/login',
            'logout_redirect_path' => '/',
        ];

        yield [
            'security',
            array_merge($options, ['check_path' => 'stub_callback']),
        ];

        yield [
            'security1',
            array_merge($options, ['check_path' => 'stub_callback1']),
        ];

        yield [
            'security1_guard',
            array_merge($options, ['check_path' => 'stub_callback1']),
        ];

        yield [
            'security2',
            array_merge($options, ['check_path' => 'stub_callback2', 'login_path' => 'stub_login']),
        ];

        yield [
            'security2_guard',
            array_merge($options, ['check_path' => 'stub_callback2', 'login_path' => 'stub_login']),
        ];
    }

    /**
     * @dataProvider providerHelperOptionsForMultipleFirewalls
     */
    public function testHelperOptionsForMultipleFirewalls(string $configuration, array $options): void
    {
        $this->skipGuardIntegrationTest($configuration);

        $container = $this->buildContainer();
        $this->loadFromFile($container, $configuration);
        $container->compile();

        $helperDefinition = $container->getDefinition('rmlev_auth0_login.helper.auth0helper');
        $optionsCollectionReference = (string) $helperDefinition->getArgument(3);
        $this->assertSame('rmlev_auth0_login.helper.auth0options_collection', $optionsCollectionReference);
        $optionsCollectionDefinition = $container->getDefinition($optionsCollectionReference);

        $this->assertSame(2, count($optionsCollectionDefinition->getMethodCalls()));
        $this->assertEquals(
            [
                ['addOptions', [new Reference('rmlev_auth0_login.helper.auth0options.stub1'), 'stub1']],
                ['addOptions', [new Reference('rmlev_auth0_login.helper.auth0options.stub2'), 'stub2']],
            ],
            $optionsCollectionDefinition->getMethodCalls(),
        );
        $this->assertEquals(
            $options,
            [
                $container
                    ->getDefinition('rmlev_auth0_login.helper.auth0options.stub1')
                    ->getArgument(0),
                $container
                    ->getDefinition('rmlev_auth0_login.helper.auth0options.stub2')
                    ->getArgument(0),
            ],
        );
    }

    public function providerHelperOptionsForMultipleFirewalls(): \Generator
    {
        $options = [
            'check_path' => '/login_check',
            'use_forward' => false,
            'require_previous_session' => false,
            'login_path' => '/login',
            'logout_redirect_path' => '/',
        ];

        yield [
            'security_multiple_firewalls',
            [
                array_merge($options, ['check_path' => '/stub1/login_check']),
                array_merge($options, ['check_path' => 'stub_callback']),
            ],
        ];

        yield [
            'security_multiple_firewalls_guard',
            [
                array_merge($options, ['check_path' => '/stub1/login_check']),
                array_merge($options, ['check_path' => 'stub_callback']),
            ],
        ];

        yield [
            'security_multiple_firewalls2',
            [
                array_merge(
                    $options,
                    [
                        'check_path' => 'stub_callback1',
                        'use_forward' => false,
                        'require_previous_session' => true,
                        'login_path' => 'stub_login_path1',
                        'logout_redirect_path' => 'stub_logout_redirect_path1',
                    ]
                ),
                array_merge(
                    $options,
                    [
                        'check_path' => 'stub_callback2',
                        'use_forward' => true,
                        'require_previous_session' => false,
                        'login_path' => 'stub_login_path2',
                        'logout_redirect_path' => 'stub_logout_redirect_path2',
                    ]
                ),
            ],
        ];

        yield [
            'security_multiple_firewalls2_guard',
            [
                array_merge(
                    $options,
                    [
                        'check_path' => 'stub_callback1',
                        'use_forward' => false,
                        'require_previous_session' => true,
                        'login_path' => 'stub_login_path1',
                        'logout_redirect_path' => 'stub_logout_redirect_path1',
                    ]
                ),
                array_merge(
                    $options,
                    [
                        'check_path' => 'stub_callback2',
                        'use_forward' => true,
                        'require_previous_session' => false,
                        'login_path' => 'stub_login_path2',
                        'logout_redirect_path' => 'stub_logout_redirect_path2',
                    ]
                ),
            ],
        ];
    }

    abstract protected function loadFromFile(ContainerBuilder $container, string $file, bool $valid = true): void;

    private function buildContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.project_dir' => 'stub',
            'kernel.container_class' => 'stub',
        ]));

        $framework = new FrameworkExtension();
        $container->registerExtension($framework);
        $this->loadFromFile($container, 'framework');

        $security = new SecurityExtension();
        $container->registerExtension($security);

        $doctrine = new DoctrineExtension();
        $container->registerExtension($doctrine);

        $auth0 = new RmlevAuth0LoginExtension();
        $container->registerExtension($auth0);
        $this->loadFromFile($container, 'auth0_login');

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

    /**
     * @param string $configuration
     * @param bool $valid
     * @return ContainerBuilder
     */
    private function compileContainer(string $configuration, bool $valid = true): ContainerBuilder
    {
        $container = $this->buildContainer();

        $this->loadFromFile($container, $configuration, $valid);
        $optionsCompilerPass = new OptionsCompilerPass();
        $optionsCompilerPass->process($container);
        $container->compile();

        return $container;
    }

    protected function isNewSecuritySystem(): bool
    {
        return interface_exists(AuthenticatorFactoryInterface::class);
    }

    /**
     * @param string $configuration
     */
    private function skipGuardIntegrationTest(string $configuration): void
    {
        if ((class_exists(AbstractGuardAuthenticator::class) === false) &&
            (strpos($configuration, '_guard') !== false)) {
            /** @phpstan-ignore-next-line  */
            $this->markTestSkipped(sprintf('Class "%s" not found', AbstractGuardAuthenticator::class));
        }
    }
}
