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

namespace Rmlev\Auth0LoginBundle\Tests\Security\Http\Authenticator;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\Auth0Credentials;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\OAuthCredentialsInterface;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\Helper\Auth0Options;
use Rmlev\Auth0LoginBundle\Helper\Auth0OptionsCollection;
use Rmlev\Auth0LoginBundle\ResponseLoader\ResponseUserDataLoader;
use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0User;
use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0UserProvider;
use Rmlev\Auth0LoginBundle\Security\Core\User\BaseUserProvider;
use Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Auth0Authenticator;
use Rmlev\Auth0LoginBundle\Tests\Fixtures\Entity\User;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\CustomAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Authentication\CustomAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\HttpUtils;

final class Auth0AuthenticatorTest extends TestCase
{
    /**
     * @var array<string|bool>
     */
    private array $options = [
        'check_path' => '/auth0/callback/test',
        'use_forward' => false,
        'login_path' => '/login',
        'default_target_path' => '/some/path',
    ];

    private Auth0Authenticator $auth0Authenticator;

    private EventDispatcher $eventDispatcher;

    private AuthenticationSuccessHandlerInterface $successHandler;

    private AuthenticationFailureHandlerInterface $failureHandler;

    protected function setUp(): void
    {
        if (!class_exists(AbstractAuthenticator::class)) {
            $this->markTestSkipped('Class ' . AbstractAuthenticator::class . ' not found');
        }

        $connectorStub = $this->createStub(ConnectorInterface::class);
        $this->eventDispatcher = new EventDispatcher();

        $this->successHandler = new CustomAuthenticationSuccessHandler(
            new DefaultAuthenticationSuccessHandler(new HttpUtils()),
            $this->options,
            'main'
        );

        $httpKernelSub = $this->createStub(HttpKernelInterface::class);

        $this->failureHandler = new CustomAuthenticationFailureHandler(
            new DefaultAuthenticationFailureHandler($httpKernelSub, new HttpUtils()),
            $this->options
        );

        $this->auth0Authenticator = new Auth0Authenticator(
            $this->createStub(HttpKernelInterface::class),
            $connectorStub,
            $this->eventDispatcher,
            new HttpUtils(),
            $this->createAuth0Helper(),
            $this->successHandler,
            $this->failureHandler,
            $this->options,
            $this->createStub(BaseUserProvider::class)
        );
    }

    public function testSupports(): void
    {
        $auth0Helper = $this->createAuth0Helper();

        $connectorStub = $this->createStub(ConnectorInterface::class);
        $userProviderStub = $this->createStub(BaseUserProvider::class);

        $auth0Authenticator = $this->createAuthenticator($connectorStub, $userProviderStub, $auth0Helper);

        $request = Request::create($this->options['check_path'] . '?code=stub');
        $this->assertTrue($auth0Authenticator->supports($request));
    }

    /**
     * @dataProvider authenticatorDataProvider
     * @param array<string> $userData
     * @param ConnectorInterface $connector
     * @param BaseUserProvider $userProvider
     * @param bool $ok
     */
    public function testAuthenticate(array $userData, ConnectorInterface $connector, BaseUserProvider $userProvider, bool $ok): void
    {
        $auth0Authenticator = $this->createAuthenticator($connector, $userProvider);

        $request = new Request();

        if ($ok) {
            $userPassport = $auth0Authenticator->authenticate($request);
            $this->assertInstanceOf(Passport::class, $userPassport);
            /** @var Auth0User|User $user */
            $user = $userPassport->getUser();
            $this->assertSame($userData['email'], $user->getUserIdentifier());
            $this->assertSame($userData['nickname'], $user->getNickname());
            $this->assertSame($userData['sub'], $user->getAuth0UserKey());
        } else {
            if (class_exists(UserNotFoundException::class)) {
                $this->expectException(UserNotFoundException::class);
            } else {
                $this->expectException(UsernameNotFoundException::class);
            }
            $userPassport = $auth0Authenticator->authenticate($request);
        }
    }

    public function authenticatorDataProvider(): \Generator
    {
        $connectorMock = $this->createMock(ConnectorInterface::class);
        $connectorMock
            ->expects($this->once())
            ->method('exchange');

        $testEmail = 'test.user@example.com';
        $userData = [
            'email' => $testEmail,
            'nickname' => 'test.nickname',
            'sub' => 'test.sub',
        ];
        $credentials = new Auth0Credentials(
            $userData,
            'idToken.test',
            'accessToken.test',
            ['openid', 'profile', 'email'],
            time() + 100,
            false
        );
        $connectorMock
            ->method('getCredentials')
            ->willReturn($credentials);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $userProvider = new Auth0UserProvider($connectorMock, $this->createAuth0Helper(), new ResponseUserDataLoader($propertyAccessor));

        yield [
            $userData,
            $connectorMock,
            $userProvider,
            true,
        ];

        $connectorMockException = $this->createMock(ConnectorInterface::class);
        $connectorMockException
            ->expects($this->once())
            ->method('exchange');

        $connectorMockException
            ->method('getCredentials')
            ->willReturn(null);

        yield [
            $userData,
            $connectorMockException,
            $userProvider,
            false,
        ];
    }

    public function testCreateToken(): void
    {
        if (!class_exists(Passport::class)) {
            $this->markTestSkipped('Class '.Passport::class.' does not exists');
        }

        $passportStub = $this->createStub(Passport::class);
        $userStub = $this->createStub(UserInterface::class);
        $userStub->method('getRoles')
            ->willReturn(['ROLE_USER']);
        $passportStub
            ->method('getUser')
            ->willReturn($userStub);
        $map = [
            ['access_token', '', 'test.token'],
            ['id_token', '', 'id.token'],
            ['expires_at', null, new \DateTimeImmutable()]
        ];
        if (method_exists(Passport::class, 'getAttribute')) {
            $passportStub->method('getAttribute')
                ->willReturnMap($map);
        }

        $connectorStub = $this->createStub(ConnectorInterface::class);
        $connectorStub
            ->method('exchange')
            ->willReturn(true);
        $credentialsStub = $this->createStub(OAuthCredentialsInterface::class);
        $credentialsStub
            ->method('getTokenAttributes')
            ->willReturn(
                new ParameterBag([
                    'access_token' => 'test.token',
                    'id_token' => 'id.token',
                    'expires_at' => new \DateTimeImmutable(),
                ])
            );
        $credentialsStub->method('getUserData')
            ->willReturn(new ParameterBag([
                'email' => 'test@example.com',
                'nickname' => 'stub',
            ]));
        $connectorStub
            ->method('getCredentials')
            ->willReturn($credentialsStub);

        $userProviderStub = $this->createStub(BaseUserProvider::class);
        $userProviderStub->method('loadUserFromAuth0Response')
            ->willReturn($this->createStub(UserInterface::class));
        if (class_exists(UserBadge::class)) {
            $userProviderStub->method('getUserIdentifier')
                ->willReturn('stub');
        }

        $auth0Authenticator = new Auth0Authenticator(
            $this->createStub(HttpKernelInterface::class),
            $connectorStub,
            $this->eventDispatcher,
            new HttpUtils(),
            $this->createAuth0Helper(),
            $this->successHandler,
            $this->failureHandler,
            $this->options,
            $userProviderStub
        );

        $request = Request::create('https://example.com');
        $request->query->set('code', 'stub.code');
        $request->query->set('state', 'stub.state');

        $auth0Authenticator->authenticate($request);
        $auth0Token = $auth0Authenticator->createToken($passportStub, 'main');

        $this->assertInstanceOf(TokenInterface::class, $auth0Token);
    }

    public function testCreateAuthenticatedToken(): void
    {
        if (!class_exists(PassportInterface::class)) {
            $this->markTestSkipped('Class '.PassportInterface::class.' does not exists');
        }
        $passportStub = $this->createStub(PassportInterface::class);
        $userStub = $this->createStub(UserInterface::class);
        $userStub->method('getRoles')
            ->willReturn(['ROLE_USER']);
        $passportStub
            ->method('getUser')
            ->willReturn($userStub);
        $map = [
            ['access_token', '', 'test.token'],
            ['id_token', '', 'id.token'],
            ['expires_at', null, new \DateTimeImmutable()]
        ];
        $passportStub->method('getAttribute')
            ->willReturnMap($map);

        $auth0Token = $this->auth0Authenticator->createAuthenticatedToken($passportStub, 'main');

        $this->assertInstanceOf(TokenInterface::class, $auth0Token);
    }

    public function testCreateAuthenticatedTokenNotContainUser()
    {
        if (!class_exists(PassportInterface::class)) {
            $this->markTestSkipped('Class '.PassportInterface::class.' does not exists');
        }

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Passport does not contain a user');

        $passportStub = $this->createStub(PassportInterface::class);

        $userData = [
            'email' => 'test.user@example.com',
            'nickname' => 'test.nickname',
            'sub' => 'test.sub',
        ];
        $credentials = new Auth0Credentials(
            $userData,
            'idToken.test',
            'accessToken.test',
            ['openid', 'profile', 'email'],
            time() + 100,
            false
        );
        $connectorMock = $this->createMock(ConnectorInterface::class);
        $connectorMock
            ->expects($this->any())
            ->method('getCredentials')
            ->willReturn($credentials);
        $auth0Authenticator = new Auth0Authenticator(
            $this->createStub(HttpKernelInterface::class),
            $connectorMock,
            $this->eventDispatcher,
            new HttpUtils(),
            $this->createAuth0Helper(),
            $this->successHandler,
            $this->failureHandler,
            $this->options,
            $this->createStub(BaseUserProvider::class)
        );

        $request = Request::create('/stub?code=stub_code');
        $auth0Authenticator->authenticate($request);
        $auth0Authenticator->createAuthenticatedToken($passportStub, 'main');
    }

    public function testCreateAuthenticatedTokenUserNotAuthenticated()
    {
        if (!class_exists(PassportInterface::class)) {
            $this->markTestSkipped('Class '.PassportInterface::class.' does not exists');
        }

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('User is not authenticated');

        $passportStub = $this->createStub(PassportInterface::class);
        $connectorMock = $this->createMock(ConnectorInterface::class);
        $connectorMock
            ->expects($this->any())
            ->method('getCredentials')
            ->willReturn(null);

        $this->auth0Authenticator->createAuthenticatedToken($passportStub, 'main');
    }

    public function testOnAuthenticationSuccess(): void
    {
        $tokenStub = $this->createStub(TokenInterface::class);

        $host = 'https://example.com';
        $request = Request::create($host.'/test/path');
        $request->setSession($this->createSession());

        $response = $this->auth0Authenticator->onAuthenticationSuccess($request, $tokenStub, 'main');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            $host.$this->options['default_target_path'],
            $response->headers->get('location')
        );
    }

    public function testOnAuthenticationFailure(): void
    {
        $host = 'https://example.com';
        $request = Request::create($host.'/some/path');
        $request->setSession($this->createSession());
        $authenticationExceptionStub = $this->createStub(AuthenticationException::class);

        $response = $this->auth0Authenticator->onAuthenticationFailure($request, $authenticationExceptionStub);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            $host.$this->options['login_path'],
            $response->headers->get('location')
        );
    }

    public function testStart(): void
    {
        $host = 'https://example.com';
        $request = Request::create($host.'/some/path');
        $request->setSession($this->createSession());

        $response = $this->auth0Authenticator->start($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            $host.$this->options['login_path'],
            $response->headers->get('location')
        );
    }

    private function createAuth0Helper(): Auth0Helper
    {
        $firewallConfigMock = $this->createMock(FirewallConfig::class);
        $firewallConfigMock->method('getName')
            ->willReturn('stub');

        $firewallMapMock = $this->createMock(FirewallMap::class);
        $firewallMapMock->method('getFirewallConfig')
            ->willReturn($firewallConfigMock);

        $auth0Options = new Auth0Options($this->options);
        $auth0OptionsCollection = new Auth0OptionsCollection();
        $auth0OptionsCollection->addOptions($auth0Options, 'stub');

        return new Auth0Helper($firewallMapMock, new HttpUtils(), new RequestStack(), $auth0OptionsCollection);
    }

    private function createAuthenticator(ConnectorInterface $connector, BaseUserProvider $userProvider, Auth0Helper $auth0Helper = null): Auth0Authenticator
    {
        $helper = $auth0Helper ?? $this->createAuth0Helper();
        return new Auth0Authenticator($this->createStub(HttpKernelInterface::class), $connector, $this->eventDispatcher, new HttpUtils(), $helper, $this->successHandler, $this->failureHandler, $this->options, $userProvider);
    }

    private function createSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }
}
