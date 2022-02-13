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

namespace Rmlev\Auth0LoginBundle\Tests\Functional;

use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\Security\Core\User\EntityUserProvider;
use Rmlev\Auth0LoginBundle\Security\Guard\Authenticator\Auth0GuardAuthenticator;
use Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Auth0Authenticator;
use Rmlev\Auth0LoginBundle\Tests\Fixtures\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

final class IntegrationTest extends KernelTestCase
{
    protected static array $kernelOptions = [
        'security' => 'security.yaml',
        'success' => true,
        'extra' => [],
    ];

    protected static function createKernel(array $options = []): KernelInterface
    {
        if (null === self::$class) {
            self::$class = self::getKernelClass();
        }

        if (isset($options['environment'])) {
            $env = $options['environment'];
        } elseif (isset($_ENV['APP_ENV'])) {
            $env = $_ENV['APP_ENV'];
        } elseif (isset($_SERVER['APP_ENV'])) {
            $env = $_SERVER['APP_ENV'];
        } else {
            $env = 'test';
        }

        if (isset($options['debug'])) {
            $debug = $options['debug'];
        } elseif (isset($_ENV['APP_DEBUG'])) {
            $debug = $_ENV['APP_DEBUG'];
        } elseif (isset($_SERVER['APP_DEBUG'])) {
            $debug = $_SERVER['APP_DEBUG'];
        } else {
            $debug = true;
        }

        return new self::$class($env, $debug, self::$kernelOptions);
    }

    public function testServicesLoaded(): void
    {
        $kernel = self::bootKernel();

        if (method_exists(KernelTestCase::class, 'getContainer')) {
            /** @phpstan-ignore-next-line  */
            $container = self::getContainer();
        } else {
            $container = self::$container;
        }
        $helper = $container->get('rmlev_auth0_login.helper.auth0helper');
        $options = $helper->getOptions();

        $entryPoint = null;
        if (self::isNewSecuritySystem()) {
            $authenticator = $container->get('rmlev_auth0_login.security.authenticator.stub');
            $entryPoint = $container->get('rmlev_auth0_login.entry_point.stub');
        } else {
            $authenticator = $container->get('rmlev_auth0_login.security.guard.authenticator.stub');
        }

        $userProvider = $container->get('security.user.provider.concrete.stub_entity_users');

        $this->assertInstanceOf(Auth0Helper::class, $helper);
        $this->assertSame('auth0_callback', $options['check_path']);
        $this->assertSame('auth0_authorize', $options['login_path']);

        if (self::isNewSecuritySystem()) {
            $this->assertInstanceOf(Auth0Authenticator::class, $authenticator);
            $this->assertSame($authenticator, $entryPoint);
        } else {
            $this->assertInstanceOf(Auth0GuardAuthenticator::class, $authenticator);
        }
        $request = Request::create('/auth0/callback');
        $request->query->set('code', 'stub');
        $this->assertTrue($authenticator->supports($request));
        $requestNotSupports = Request::create('/auth0/callback');
        $this->assertFalse($authenticator->supports($requestNotSupports));

        $this->assertInstanceOf(EntityUserProvider::class, $userProvider);
        $this->assertTrue($userProvider->supportsClass(User::class));
    }

    private static function isNewSecuritySystem(): bool
    {
        return interface_exists(AuthenticatorFactoryInterface::class);
    }

    protected function tearDown(): void
    {
        $kernel = self::bootKernel();
        $cacheDir = $kernel->getCacheDir();
        $kernel->shutdown();

        parent::tearDown();

        @passthru(sprintf('rm -rf %s', $cacheDir));
    }
}
