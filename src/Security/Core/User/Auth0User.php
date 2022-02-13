<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Security\Core\User;

use Symfony\Component\Security\Core\User\UserInterface;

final class Auth0User implements UserInterface
{
    private string $email;

    private ?string $name = null;

    private ?string $nickname = null;

    private ?string $auth0UserKey = null;

    private ?string $avatar = null;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        return ['ROLE_USER', 'ROLE_AUTH0_USER'];
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): void
    {
        $this->nickname = $nickname;
    }

    public function getAuth0UserKey(): ?string
    {
        return $this->auth0UserKey;
    }

    public function setAuth0UserKey(string $auth0UserKey): void
    {
        $this->auth0UserKey = $auth0UserKey;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
    }
}
