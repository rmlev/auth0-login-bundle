<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Token;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class Auth0Token extends PostAuthenticationToken
{
    private ?string $accessToken = null;
    private ?string $idToken = null;
    private ?string $refreshToken = null;
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct(Passport $passport, string $firewallName, ParameterBag $attributes = null)
    {
        parent::__construct($passport->getUser(), $firewallName, $passport->getUser()->getRoles());

        if (method_exists($passport, 'getAttribute')) {
            $this->accessToken = $passport->getAttribute('access_token', '');
            $this->idToken = $passport->getAttribute('id_token', '');
            $this->refreshToken = $passport->getAttribute('refresh_token', '');
            $this->expiresAt = $passport->getAttribute('expires_at');
        } elseif ($attributes) {
            $this->accessToken = $attributes->get('access_token');
            $this->idToken = $attributes->get('id_token');
            $this->refreshToken = $attributes->get('refresh_token');
            $this->expiresAt = $attributes->get('expires_at');
        }
    }

    public function getIdToken(): string
    {
        return $this->idToken;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * {@inheritdoc}
     */
    public function __serialize(): array
    {
        return [$this->accessToken, $this->idToken, $this->refreshToken, $this->expiresAt, parent::__serialize()];
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize(array $data): void
    {
        [$this->accessToken, $this->idToken, $this->refreshToken, $this->expiresAt, $parentData] = $data;
        parent::__unserialize($parentData);
    }
}
