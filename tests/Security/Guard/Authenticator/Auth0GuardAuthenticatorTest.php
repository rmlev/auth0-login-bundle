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

namespace Rmlev\Auth0LoginBundle\Tests\Security\Guard\Authenticator;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\Auth0Credentials;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\Helper\Auth0Options;
use Rmlev\Auth0LoginBundle\Helper\Auth0OptionsCollection;
use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0User;
use Rmlev\Auth0LoginBundle\Security\Core\User\BaseUserProvider;
use Rmlev\Auth0LoginBundle\Security\Guard\Authenticator\Auth0GuardAuthenticator;
use Rmlev\Auth0LoginBundle\Security\Guard\Token\Auth0GuardToken;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Authentication\CustomAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Authentication\CustomAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

final class Auth0GuardAuthenticatorTest extends TestCase
{
    private array $options = [
        'check_path' => '/auth0/callback/test',
        'use_forward' => false,
        'login_path' => '/login',
        'default_target_path' => '/some/path',
    ];

    private string $host = 'https://example.com';

    private CustomAuthenticationSuccessHandler $successHandler;
    private CustomAuthenticationFailureHandler $failureHandler;
    private Auth0GuardAuthenticator $auth0GuardAuthenticator;

    protected function setUp(): void
    {
        if (class_exists(AbstractGuardAuthenticator::class) === false) {
            $this->markTestSkipped(sprintf('Class "%s" not found', AbstractGuardAuthenticator::class));
        }

        $connectorStub = $this->createStub(ConnectorInterface::class);
        $connectorStub
            ->method('exchange')
            ->willReturn(true);

        $firewallConfigMock = $this->createMock(FirewallConfig::class);
        $firewallConfigMock->method('getName')
            ->willReturn('stub');

        $firewallMapMock = $this->createMock(FirewallMap::class);
        $firewallMapMock->method('getFirewallConfig')
            ->willReturn($firewallConfigMock);

        $auth0Options = new Auth0Options($this->options);
        $auth0OptionsCollection = new Auth0OptionsCollection();
        $auth0OptionsCollection->addOptions($auth0Options, 'stub');

        $auth0Helper = new Auth0Helper($firewallMapMock, new HttpUtils(), new RequestStack(), $auth0OptionsCollection);

        $this->successHandler = new CustomAuthenticationSuccessHandler(
            new DefaultAuthenticationSuccessHandler(new HttpUtils()),
            $this->options,
            'stub'
        );

        $httpKernelSub = $this->createStub(HttpKernelInterface::class);
        $this->failureHandler = new CustomAuthenticationFailureHandler(
            new DefaultAuthenticationFailureHandler($httpKernelSub, new HttpUtils()),
            $this->options
        );

        $userProviderStub = $this->createStub(BaseUserProvider::class);

        $this->auth0GuardAuthenticator = new Auth0GuardAuthenticator(
            $this->createStub(HttpKernelInterface::class),
            $connectorStub,
            new EventDispatcher(),
            new HttpUtils(),
            $auth0Helper,
            $this->successHandler,
            $this->failureHandler,
            $this->options,
            $userProviderStub
        );
    }

    public function testSupports(): void
    {
        $request = Request::create($this->host . $this->options['check_path']);
        $request->query->set('code', 'stub');

        $this->assertTrue($this->auth0GuardAuthenticator->supports($request));
    }

    public function testNotSupports(): void
    {
        $request = Request::create($this->host . $this->options['check_path']);

        $this->assertFalse($this->auth0GuardAuthenticator->supports($request));
    }

    public function testGetCredentials(): void
    {
        $request = Request::create($this->host.$this->options['check_path']);
        $request->query->set('code', 'code_stub');
        $request->query->set('state', 'state_stub');

        $credentials = $this->auth0GuardAuthenticator->getCredentials($request);
        $this->assertIsArray($credentials);
        $this->assertSame('code_stub', $credentials['code']);
        $this->assertSame('state_stub', $credentials['state']);
        $this->assertSame(
            $this->host.$this->options['check_path'],
            $credentials['redirect_uri']
        );
    }

    /**
     * @dataProvider getCredentials
     */
    public function testGetUser(array $credentials): void
    {
        $connectorMock = $this->createMock(ConnectorInterface::class);
        $connectorMock
            ->expects($this->once())
            ->method('exchange')
            ->willReturn(true)
        ;

        $userData = [
            'email' => 'test.user@example.com',
            'nickname' => 'test.nickname',
            'sub' => 'test.sub',
        ];
        $auth0Credentials = new Auth0Credentials(
            $userData,
            'idToken.test',
            'accessToken.test',
            ['openid', 'profile', 'email'],
            time() + 100,
            false
        );

        $connectorMock
            ->expects($this->once())
            ->method('getCredentials')
            ->willReturn($auth0Credentials);

        $eventDispatcher = new EventDispatcher();

        $userMock = $this->createMock(UserInterface::class);
        $userMock
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_USER']);

        $userProviderMock = $this->createMock(BaseUserProvider::class);
        $userProviderMock
            ->expects($this->once())
            ->method('loadUserFromAuth0Response')
            ->willReturn($userMock);

        $firewallMapStab = $this->createStub(FirewallMap::class);
        $auth0GuardAuthenticator = new Auth0GuardAuthenticator(
            $this->createStub(HttpKernelInterface::class),
            $connectorMock,
            $eventDispatcher,
            new HttpUtils(),
            new Auth0Helper($firewallMapStab, new HttpUtils(), new RequestStack(), new Auth0OptionsCollection()),
            $this->successHandler,
            $this->failureHandler,
            $this->options,
            $userProviderMock
        );

        $this->assertInstanceOf(UserInterface::class, $auth0GuardAuthenticator->getUser($credentials, $userProviderMock));

        $token = $auth0GuardAuthenticator->createAuthenticatedToken($userMock, 'stub');
        $this->assertInstanceOf(Auth0GuardToken::class, $token);
        $this->assertSame($userMock, $token->getUser());
    }

    /**
     * @dataProvider getCredentials
     */
    public function testGetUserNoCredentials(array $credentials): void
    {
        if (class_exists(UserNotFoundException::class)) {
            /** @phpstan-ignore-next-line  */
            $this->expectException(UserNotFoundException::class);
        } else {
            $this->expectException(UsernameNotFoundException::class);
        }

        $connectorMock = $this->createMock(ConnectorInterface::class);
        $connectorMock
            ->expects($this->once())
            ->method('exchange')
            ->willReturn(true)
        ;

        $connectorMock
            ->expects($this->once())
            ->method('getCredentials')
            ->willReturn(null);

        $eventDispatcher = new EventDispatcher();

        $userProviderMock = $this->createMock(BaseUserProvider::class);

        $firewallMapStab = $this->createStub(FirewallMap::class);
        $auth0GuardAuthenticator = new Auth0GuardAuthenticator(
            $this->createStub(HttpKernelInterface::class),
            $connectorMock,
            $eventDispatcher,
            new HttpUtils(),
            new Auth0Helper($firewallMapStab, new HttpUtils(), new RequestStack(), new Auth0OptionsCollection()),
            $this->successHandler,
            $this->failureHandler,
            $this->options,
            $userProviderMock
        );

        $auth0GuardAuthenticator->getUser($credentials, $userProviderMock);
    }

    public function testCreateAuthenticatedToken(): void
    {
        $this->expectException(AuthenticationException::class);
        $userStub = $this->createStub(UserInterface::class);

        $this->auth0GuardAuthenticator->createAuthenticatedToken($userStub, 'stub');
    }

    /**
     * @dataProvider getCredentials
     */
    public function testCheckCredentials(array $credentials): void
    {
        $user = new Auth0User('test@example.com');

        $this->assertTrue($this->auth0GuardAuthenticator->checkCredentials($credentials, $user));
    }

    /**
     * @dataProvider getCredentials
     */
    public function testFailCheckCredentials(array $credentials): void
    {
        $user = new Auth0User('');

        $this->assertFalse($this->auth0GuardAuthenticator->checkCredentials($credentials, $user));
    }

    public function getCredentials(): \Generator
    {
        yield [[
            'code' => 'code_stub',
            'state' => 'state_stub',
            'redirect_uri' => $this->host.$this->options['check_path'],
        ]];
    }

    public function testOnAuthenticationFailure(): void
    {
        $host = 'https://example.com';
        $request = Request::create($host.'/some/path');
        $request->setSession($this->createSession());
        $authenticationExceptionStub = $this->createStub(AuthenticationException::class);

        $response = $this->auth0GuardAuthenticator->onAuthenticationFailure($request, $authenticationExceptionStub);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            $host.$this->options['login_path'],
            $response->headers->get('location')
        );
    }

    public function testOnAuthenticationSuccess(): void
    {
        $tokenStub = $this->createStub(TokenInterface::class);

        $host = 'https://example.com';
        $request = Request::create($host.'/test/path');
        $request->setSession($this->createSession());

        $response = $this->auth0GuardAuthenticator->onAuthenticationSuccess($request, $tokenStub, 'main');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            $host.$this->options['default_target_path'],
            $response->headers->get('location')
        );
    }

    public function testStart(): void
    {
        $host = 'https://example.com';
        $request = Request::create($host.'/some/path');
        $request->setSession($this->createSession());

        $response = $this->auth0GuardAuthenticator->start($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            $host.$this->options['login_path'],
            $response->headers->get('location')
        );
    }

    private function createSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }
}
