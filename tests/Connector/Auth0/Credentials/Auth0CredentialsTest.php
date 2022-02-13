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

namespace Rmlev\Auth0LoginBundle\Tests\Connector\Auth0\Credentials;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\Auth0Credentials;
use Symfony\Component\HttpFoundation\ParameterBag;

final class Auth0CredentialsTest extends TestCase
{
    private array $userData = [
        'email' => 'test@example.com',
        'nickname' => 'stub',
    ];
    private string $idToken = 'id_token.test';
    private string $accessToken = 'access_token.test';
    private array $scope = ['email', 'profile'];
    private int $accessTokenExpiration;
    private bool $accessTokenExpired = false;
    private string $refreshToken = 'refresh_token.test';

    private Auth0Credentials $auth0Credentials;

    protected function setUp(): void
    {
        $this->accessTokenExpiration = time() + 1000;

        $this->auth0Credentials = new Auth0Credentials(
            $this->userData,
            $this->idToken,
            $this->accessToken,
            $this->scope,
            $this->accessTokenExpiration,
            $this->accessTokenExpired,
            $this->refreshToken
        );
    }

    public function testGetUserData(): void
    {
        $this->assertEquals(new ParameterBag($this->userData), $this->auth0Credentials->getUserData());
    }

    public function testGetIdToken(): void
    {
        $this->assertSame($this->idToken, $this->auth0Credentials->getIdToken());
    }

    public function testGetAccessToken(): void
    {
        $this->assertSame($this->accessToken, $this->auth0Credentials->getAccessToken());
    }

    public function testGetAccessTokenScope(): void
    {
        $this->assertEquals(new ParameterBag($this->scope), $this->auth0Credentials->getAccessTokenScope());
    }

    public function testGetAccessTokenExpiration(): void
    {
        $this->assertSame($this->accessTokenExpiration, $this->auth0Credentials->getAccessTokenExpiration());
    }

    public function testIsAccessTokenExpired(): void
    {
        $this->assertSame($this->accessTokenExpired, $this->auth0Credentials->isAccessTokenExpired());
    }

    public function testGetRefreshToken(): void
    {
        $this->assertSame($this->refreshToken, $this->auth0Credentials->getRefreshToken());
    }

    public function testGetTokenAttributes(): void
    {
        $attributes = new ParameterBag([
            'access_token' => $this->accessToken,
            'id_token' => $this->idToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => new \DateTimeImmutable('@'.$this->accessTokenExpiration)
        ]);

        $this->assertEquals($attributes, $this->auth0Credentials->getTokenAttributes());
    }
}
