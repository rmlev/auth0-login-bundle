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

namespace Rmlev\Auth0LoginBundle\Tests\Functional\App\src\Connector\Auth0;

use Auth0\SDK\Contract\Auth0Interface;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\Auth0Credentials;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\OAuthCredentialsInterface;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Stub for test success authentication process
 */
final class StubConnector implements ConnectorInterface
{
    private array $userData;

    private string $idToken;
    private string $accessToken;
    private ?string $refreshToken = null;

    public function __construct(array $userData, string $idToken, string $accessToken, string $refreshToken = null)
    {
        $this->userData = $userData;
        $this->idToken = $idToken;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    public function login(?string $redirectUrl = null, ?array $params = null): string
    {
        return '/auth0_server_stub?redirect_url='.$redirectUrl;
    }

    public function exchange(?string $redirectUri = null, ?string $code = null, ?string $state = null): bool
    {
        return true;
    }

    public function getCredentials(): ?OAuthCredentialsInterface
    {
        return new Auth0Credentials(
            $this->userData,
            $this->idToken,
            $this->accessToken,
            ['openid', 'profile', 'email'],
            time() + 100,
            false,
            $this->refreshToken
        );
    }

    public function getUser(): ?ParameterBag
    {
        return new ParameterBag($this->userData);
    }

    public function getIdToken(): ?string
    {
        return $this->idToken;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function logout(?string $returnUri = null, ?array $params = null): string
    {
       return $returnUri;
    }

    public function clear(bool $transient = true): Auth0Interface
    {
        $testAuth0Factory = new StubAuth0Factory();
        return $testAuth0Factory->createAuth0Stub();
    }
}
