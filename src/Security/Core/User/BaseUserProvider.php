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
use Symfony\Component\Security\Core\User\UserInterface;

abstract class BaseUserProvider implements Auth0AwareUserProviderInterface
{
    protected ConnectorInterface $connector;
    protected Auth0Helper $auth0Helper;
    protected ResponseUserDataLoaderInterface $responseUserDataLoader;

    public function __construct(ConnectorInterface $connector, Auth0Helper $auth0Helper, ResponseUserDataLoaderInterface $responseUserDataLoader)
    {
        $this->connector = $connector;
        $this->auth0Helper = $auth0Helper;
        $this->responseUserDataLoader = $responseUserDataLoader;
    }

    /**
     * @inheritDoc
     */
    public function getUserIdentifier(ParameterBag $userData): string
    {
        $identifier = $userData->get(
            $this->responseUserDataLoader->getIdentifierAuth0Side()
        );

        if (!$identifier) {
            throw $this->auth0Helper->createUserNotFoundException('User identifier not found');
        }

        return $identifier;
    }

    public function loadUserFromAuth0Response(ParameterBag $userData): ?UserInterface
    {
        $identifier = $this->getUserIdentifier($userData);

        return $this->loadUser($identifier, $userData);
    }

    protected function loadUserData(string $identifier): ParameterBag
    {
        $credentials = $this->connector->getCredentials();
        if (!$credentials) {
            throw $this->auth0Helper->createUserNotFoundException(sprintf("User '%s' not found.", $identifier));
        }

        $userData = $credentials->getUserData();
        if (!$this->responseUserDataLoader->checkUserIdentifier($identifier, $userData)) {
            throw $this->auth0Helper->createUserNotFoundException(sprintf("User '%s' not found.", $identifier));
        }

        return $userData;
    }

    abstract protected function loadUser(string $identifier, ParameterBag $userData): ?UserInterface;
}
