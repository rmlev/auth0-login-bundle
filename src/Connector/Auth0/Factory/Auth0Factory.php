<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Connector\Auth0\Factory;

use Auth0\SDK\Auth0;

final class Auth0Factory
{
    private string $auth0Domain;
    private string $auth0ClientId;
    private string $auth0ClientSecret;
    private string $auth0CookieSecret;

    public function __construct(
        string $auth0Domain,
        string $auth0ClientId,
        string $auth0ClientSecret,
        string $auth0CookieSecret
    )
    {
        $this->auth0Domain = $auth0Domain;
        $this->auth0ClientId = $auth0ClientId;
        $this->auth0ClientSecret = $auth0ClientSecret;
        $this->auth0CookieSecret = $auth0CookieSecret;
    }

    public function getAuth0(): Auth0
    {
        return new Auth0([
            'domain' => $this->auth0Domain,
            'clientId' => $this->auth0ClientId,
            'clientSecret' => $this->auth0ClientSecret,
            'cookieSecret' => $this->auth0CookieSecret
        ]);
    }
}
