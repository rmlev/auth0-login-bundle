<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Security\Guard\Token;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class Auth0GuardToken extends PostAuthenticationGuardToken
{
    private ?string $accessToken = null;
    private ?string $idToken = null;
    private ?string $refreshToken = null;
    private \DateTimeImmutable $expiresAt;

    public function __construct(UserInterface $user, string $firewallName, ParameterBag $attributes)
    {
        parent::__construct($user, $firewallName, $user->getRoles());

        $this->accessToken = $attributes->get('access_token');
        $this->idToken = $attributes->get('id_token');
        $this->refreshToken = $attributes->get('refresh_token');
        $this->expiresAt = $attributes->get('expires_at');
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getIdToken(): ?string
    {
        return $this->idToken;
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
