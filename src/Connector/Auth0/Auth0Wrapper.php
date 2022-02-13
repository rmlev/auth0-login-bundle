<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Connector\Auth0;

use Auth0\SDK\Auth0;
use Auth0\SDK\Contract\Auth0Interface;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\Auth0Credentials;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Factory\Auth0Factory;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

/**
 * Wrapper for Auth0 SDK
 */
final class Auth0Wrapper implements ConnectorInterface
{
    private Auth0 $auth0;
    private Auth0Factory $auth0Factory;

    public function __construct(Auth0Factory $auth0Factory)
    {
        $this->auth0 = $auth0Factory->getAuth0();
        $this->auth0Factory = $auth0Factory;
    }

    /**
     * Return the url to the login page.
     *
     * @param string|null                 $redirectUrl Optional. URI to return to after logging out. Defaults to the SDK's configured redirectUri.
     * @param array<int|string|null>|null $params Additional parameters to include with the request.
     *
     * @throws \Auth0\SDK\Exception\ConfigurationException When a Client ID is not configured.
     * @throws \Auth0\SDK\Exception\ConfigurationException When `redirectUri` is not specified, and supplied SdkConfiguration does not have a default redirectUri configured.
     */
    public function login(?string $redirectUrl = null, ?array $params = null): string
    {
        $auth0 = $this->auth0Factory->getAuth0();
        return $auth0->login($redirectUrl, $params);
    }

    /**
     * Exchange authorization code for access, ID, and refresh tokens.
     *
     * @param string|null $redirectUri  Optional. Redirect URI sent with authorize request. Defaults to the SDK's configured redirectUri.
     * @param string|null $code         Optional. The value of the `code` parameter. One will be extracted from $_GET if not specified.
     * @param string|null $state        Optional. The value of the `state` parameter. One will be extracted from $_GET if not specified.
     *
     * @throws \Auth0\SDK\Exception\StateException   If the code value is missing from the request parameters.
     * @throws \Auth0\SDK\Exception\StateException   If the state value is missing from the request parameters, or otherwise invalid.
     * @throws \Auth0\SDK\Exception\StateException   If access token is missing from the response.
     * @throws \Auth0\SDK\Exception\NetworkException When the API request fails due to a network error.
     */
    public function exchange(?string $redirectUri = null, ?string $code = null, ?string $state = null): bool
    {
        return $this->auth0->exchange($redirectUri, $code, $state);
    }

    /**
     * Return an object representing the current session credentials (including id token, access token, access token expiration, refresh token and user data) without triggering an authorization flow. Returns null when session data is not available.
     */
    public function getCredentials(): ?Auth0Credentials
    {
        $credentials = $this->auth0->getCredentials();
        if (!$credentials) {
            return null;
        }
        if (time() >= $credentials->accessTokenExpiration) {
            throw new CustomUserMessageAuthenticationException('Token expired');
        }

        return new Auth0Credentials(
            $credentials->user,
            $credentials->idToken,
            $credentials->accessToken,
            $credentials->accessTokenScope,
            $credentials->accessTokenExpiration,
            $credentials->accessTokenExpired,
            $credentials->refreshToken
        );
    }

    /**
     * Get userinfo from an active session
     *
     * @return ParameterBag|null
     */
    public function getUser(): ?ParameterBag
    {
        $user = $this->auth0->getUser();
        if ($user) {
            return new ParameterBag($user);
        }

        return null;
    }

    /**
     * Get ID token from an active session
     */
    public function getIdToken(): ?string
    {
        return $this->auth0->getIdToken();
    }

    /**
     * Get access token from an active session
     */
    public function getAccessToken(): ?string
    {
        return $this->auth0->getAccessToken();
    }

    /**
     * Get refresh token from an active session
     */
    public function getRefreshToken(): ?string
    {
        return $this->auth0->getRefreshToken();
    }

    /**
     * Delete any persistent data and clear out all stored properties, and return the URI to Auth0 /logout endpoint for redirection.
     *
     * @param string|null                 $returnUri Optional. URI to return to after logging out. Defaults to the SDK's configured redirectUri.
     * @param array<int|string|null>|null $params    Optional. Additional parameters to include with the request.
     *
     * @throws \Auth0\SDK\Exception\ConfigurationException When a Client ID is not configured.
     * @throws \Auth0\SDK\Exception\ConfigurationException When `returnUri` is not specified, and supplied SdkConfiguration does not have a default redirectUri configured.
     */
    public function logout(?string $returnUri = null, ?array $params = null): string
    {
        return $this->auth0->logout($returnUri, $params);
    }

    /**
     * Delete any persistent data and clear out all stored properties.
     *
     * @param bool $transient When true, data in transient storage is also cleared.
     */
    public function clear(bool $transient = true): Auth0Interface
    {
        return $this->auth0->clear($transient);
    }
}
