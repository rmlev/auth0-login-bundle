<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials;

use Symfony\Component\HttpFoundation\ParameterBag;

final class Auth0Credentials implements OAuthCredentialsInterface
{
    private ParameterBag $userData;
    private string $idToken;
    private string $accessToken;
    private ParameterBag $accessTokenScope;
    private int $accessTokenExpiration;
    private bool $accessTokenExpired;
    private ?string $refreshToken = null;

    public function __construct(
        array $userData,
        string $idToken,
        string $accessToken,
        array $accessTokenScope,
        int $accessTokenExpiration,
        bool $accessTokenExpired,
        string $refreshToken = null
    )
    {
        $this->userData = new ParameterBag($userData);
        $this->idToken = $idToken;
        $this->accessToken = $accessToken;
        $this->accessTokenScope = new ParameterBag($accessTokenScope);
        $this->accessTokenExpiration = $accessTokenExpiration;
        $this->accessTokenExpired = $accessTokenExpired;
        $this->refreshToken = $refreshToken;
    }

    public function getUserData(): ParameterBag
    {
        return $this->userData;
    }

    public function getIdToken(): ?string
    {
        return $this->idToken;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getAccessTokenScope(): ParameterBag
    {
        return $this->accessTokenScope;
    }

    public function getAccessTokenExpiration(): int
    {
        return $this->accessTokenExpiration;
    }

    public function isAccessTokenExpired(): bool
    {
        return $this->accessTokenExpired;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getTokenAttributes(): ParameterBag
    {
        $tokenAttributes = new ParameterBag();
        $tokenAttributes->set('access_token', $this->getAccessToken());
        $tokenAttributes->set('id_token', $this->getIdToken());
        $tokenAttributes->set('refresh_token', $this->getRefreshToken());
        $tokenAttributes->set(
            'expires_at',
            new \DateTimeImmutable('@'.$this->getAccessTokenExpiration())
        );

        return $tokenAttributes;
    }
}
