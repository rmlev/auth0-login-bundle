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

use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\ResponseLoader\ResponseUserDataLoaderInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

final class Auth0UserProvider extends BaseUserProvider
{
    protected ConnectorInterface $connector;
    protected Auth0Helper $auth0Helper;

    public function __construct(
        ConnectorInterface $connector,
        Auth0Helper $auth0Helper,
        ResponseUserDataLoaderInterface $responseUserDataLoader
    )
    {
        parent::__construct($connector, $auth0Helper, $responseUserDataLoader);

        $this->connector = $connector;
        $this->auth0Helper = $auth0Helper;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $userData = $this->loadUserData($identifier);
        return $this->loadUser($identifier, $userData);
    }

    public function loadUserByIdentifierFromAuth0Response(string $identifier): ?UserInterface
    {
        return $this->loadUserByIdentifier($identifier);
    }

    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$this->supportsClass(\get_class($user))) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s"', \get_class($user)));
        }

        /** @phpstan-ignore-next-line  */
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    /**
     * @inheritDoc
     */
    public function supportsClass($class): bool
    {
        return Auth0User::class === $class;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * @param string $identifier
     * @param ParameterBag $userData
     * @return Auth0User
     */
    protected function loadUser(string $identifier, ParameterBag $userData): Auth0User
    {
        $user = new Auth0User($identifier);

        $this->responseUserDataLoader->loadUserProperties($user, $userData);
        return $user;
    }
}
