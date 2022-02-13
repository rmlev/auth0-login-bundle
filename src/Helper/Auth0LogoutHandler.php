<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Helper;

use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Security\Guard\Token\Auth0GuardToken;
use Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Token\Auth0Token;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class Auth0LogoutHandler
{
    private ConnectorInterface $connector;
    private Auth0Helper $auth0Helper;
    private TokenStorageInterface $tokenStorage;

    public function __construct(ConnectorInterface $connector, Auth0Helper $auth0Helper, TokenStorageInterface $tokenStorage)
    {
        $this->connector = $connector;
        $this->auth0Helper = $auth0Helper;
        $this->tokenStorage = $tokenStorage;
    }

    public function onLogout(): RedirectResponse
    {
        $token = $this->tokenStorage->getToken();
        $isAuth0Token = ($token instanceof Auth0Token);
        if ($token instanceof Auth0GuardToken) {
            $isAuth0Token = true;
        }

        $logoutUrl = '/';
        if ($isAuth0Token) {
            $returnURI = $this->auth0Helper->getLogoutReturnURI();
            $logoutUrl = $this->connector->logout($returnURI);
        }

        return new RedirectResponse($logoutUrl);
    }
}
