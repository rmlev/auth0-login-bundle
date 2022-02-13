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

namespace Rmlev\Auth0LoginBundle\Tests\Connector\Auth0;

use Auth0\SDK\Auth0;
use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Auth0Wrapper;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\OAuthCredentialsInterface;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Factory\Auth0Factory;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class Auth0WrapperTest extends TestCase
{
    private string $domain;
    private string $client_id;

    private Auth0Wrapper $auth0Wrapper;

    protected function setUp(): void
    {
        $this->domain = 'domain.test';
        $this->client_id = 'client_id.test';
        $client_secret = 'client_secret.test';
        $cookie_secret = 'cookie_secret.test';

        $auth0Factory = new Auth0Factory(
            $this->domain,
            $this->client_id,
            $client_secret,
            $cookie_secret
        );
        $this->auth0Wrapper = new Auth0Wrapper($auth0Factory);
    }

    public function testLogin(): void
    {
        $redirect_uri = 'stub_redirect';

        $auth0Mock = $this->createMock(Auth0::class);
        $auth0Mock
            ->expects($this->once())
            ->method('login')
            ->with($redirect_uri)
            ->willReturn('https://'.$this->domain.'/authorize?client_id='.$this->client_id.'&redirect_uri='.$redirect_uri);
        $auth0FactoryMock = $this->createMock(Auth0Factory::class);
        $auth0FactoryMock
            ->expects($this->exactly(2))
            ->method('getAuth0')
            ->willReturn($auth0Mock);
        $auth0Wrapper = new Auth0Wrapper($auth0FactoryMock);

        $loginLink = $auth0Wrapper->login($redirect_uri);

        $request = Request::create($loginLink);
        $this->assertSame($this->domain, $request->getHost());
        $this->assertSame($this->client_id, $request->query->get('client_id'));
        $this->assertSame($redirect_uri, $request->query->get('redirect_uri'));
    }

    public function testLogout(): void
    {
        $return_uri = 'stub_return';

        $logoutLink = $this->auth0Wrapper->logout($return_uri);
        $request = Request::create($logoutLink);
        $this->assertSame($this->domain, $request->getHost());
        $this->assertSame($this->client_id, $request->query->get('client_id'));
        $this->assertSame($return_uri, $request->query->get('returnTo'));
    }

    public function testExchange(): void
    {
        $redirect_uri = 'stub_redirect';
        $code = 'stub_code';
        $state = 'stub_state';

        $auth0Mock = $this->createMock(Auth0::class);
        $auth0Mock
            ->expects($this->once())
            ->method('exchange')
            ->with($redirect_uri, $code, $state)
            ->willReturn(true);

        $auth0Wrapper = $this->getAuth0Wrapper($auth0Mock);
        $this->assertTrue($auth0Wrapper->exchange($redirect_uri, $code, $state));
    }

    public function testGetCredentials(): void
    {
        $auth0Credentials = (object) [
            'user' => ['user_stub'],
            'idToken' => 'user_stub',
            'accessToken' => 'user_stub',
            'accessTokenScope' => ['stub'],
            'accessTokenExpiration' => time() + 100,
            'accessTokenExpired' => false,
            'refreshToken' => null,
        ];

        $auth0Mock = $this->createMock(Auth0::class);
        $auth0Mock
            ->expects($this->once())
            ->method('getCredentials')
            ->willReturn($auth0Credentials);

        $auth0Wrapper = $this->getAuth0Wrapper($auth0Mock);
        $this->assertInstanceOf(
            OAuthCredentialsInterface::class,
            $auth0Wrapper->getCredentials()
        );
    }

    public function testGetNullCredentials(): void
    {
        $auth0Mock = $this->createMock(Auth0::class);
        $auth0Mock
            ->expects($this->once())
            ->method('getCredentials')
            ->willReturn(null);

        $auth0Wrapper = $this->getAuth0Wrapper($auth0Mock);
        $this->assertNull($auth0Wrapper->getCredentials());
    }

    public function testGetExpiredCredentials(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);

        $auth0Credentials = (object) [
            'user' => ['user_stub'],
            'idToken' => 'user_stub',
            'accessToken' => 'user_stub',
            'accessTokenScope' => ['stub'],
            'accessTokenExpiration' => time() - 100,
            'accessTokenExpired' => true,
            'refreshToken' => null,
        ];

        $auth0Mock = $this->createMock(Auth0::class);
        $auth0Mock
            ->expects($this->once())
            ->method('getCredentials')
            ->willReturn($auth0Credentials);

        $auth0Wrapper = $this->getAuth0Wrapper($auth0Mock);
        $auth0Wrapper->getCredentials();
    }

    /**
     * @dataProvider getUserDataProvider
     */
    public function testGetUser(?array $userData, bool $haveUser): void
    {
        $auth0Mock = $this->createMock(Auth0::class);
        $auth0Mock
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($userData);

        $auth0Wrapper = $this->getAuth0Wrapper($auth0Mock);
        $user = $auth0Wrapper->getUser();
        if ($haveUser) {
            $this->assertInstanceOf(ParameterBag::class, $user);
            $this->assertEquals(new ParameterBag($userData), $user);
        } else {
            $this->assertNull($user);
        }
    }

    public function getUserDataProvider(): \Generator
    {
        yield [
            ['email' => 'stub'], true
        ];

        yield [
            null, false
        ];
    }

    public function testGetIdToken(): void
    {
        $idToken = 'id_token.stub';

        $auth0Mock = $this->createMock(Auth0::class);
        $auth0Mock
            ->expects($this->once())
            ->method('getIdToken')
            ->willReturn($idToken);

        $auth0Wrapper = $this->getAuth0Wrapper($auth0Mock);
        $this->assertSame($idToken, $auth0Wrapper->getIdToken());
    }

    public function testGetAccessToken(): void
    {
        $accessToken = 'access_token.stub';

        $auth0Mock = $this->createMock(Auth0::class);
        $auth0Mock
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $auth0Wrapper = $this->getAuth0Wrapper($auth0Mock);
        $this->assertSame($accessToken, $auth0Wrapper->getAccessToken());
    }

    public function testGetRefreshToken(): void
    {
        $refreshToken = 'refresh_token.stub';

        $auth0Mock = $this->createMock(Auth0::class);
        $auth0Mock
            ->expects($this->once())
            ->method('getRefreshToken')
            ->willReturn($refreshToken);

        $auth0Wrapper = $this->getAuth0Wrapper($auth0Mock);
        $this->assertSame($refreshToken, $auth0Wrapper->getRefreshToken());
    }

    public function testClear(): void
    {
        $auth0Mock = $this->createMock(Auth0::class);
        $auth0Mock
            ->expects($this->once())
            ->method('clear')
            ->willReturn($auth0Mock);

        $auth0Wrapper = $this->getAuth0Wrapper($auth0Mock);
        $this->assertSame($auth0Mock, $auth0Wrapper->clear());
    }

    /**
     * @param Auth0 $auth0Mock
     * @return Auth0Wrapper
     */
    protected function getAuth0Wrapper(Auth0 $auth0Mock): Auth0Wrapper
    {
        $auth0FactoryMock = $this->createMock(Auth0Factory::class);
        $auth0FactoryMock
            ->expects($this->once())
            ->method('getAuth0')
            ->willReturn($auth0Mock);

        return new Auth0Wrapper($auth0FactoryMock);
    }
}
