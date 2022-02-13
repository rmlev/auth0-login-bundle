<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Controller;

use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

final class LoginController
{
    private ConnectorInterface $connector;
    private Auth0Helper $auth0Helper;

    public function __construct(
        ConnectorInterface $connector,
        Auth0Helper $auth0Helper
    )
    {
        $this->connector = $connector;
        $this->auth0Helper = $auth0Helper;
    }

    public function authStartAction(string $firewall = null): RedirectResponse
    {
        $redirectURI = $this->auth0Helper->getRedirectURI($firewall);
        (new Request())->overrideGlobals();

        $this->connector->clear(true);
        $loginUrl = $this->connector->login($redirectURI);

        return new RedirectResponse($loginUrl);
    }
}
