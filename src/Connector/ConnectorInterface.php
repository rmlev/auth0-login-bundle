<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Connector;

use Auth0\SDK\Contract\Auth0Interface;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\OAuthCredentialsInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

interface ConnectorInterface
{
    public function login(?string $redirectUrl = null, ?array $params = null): string;

    public function exchange(?string $redirectUri = null, ?string $code = null, ?string $state = null): bool;

    public function getCredentials(): ?OAuthCredentialsInterface;

    public function getUser(): ?ParameterBag;

    public function getIdToken(): ?string;

    public function getAccessToken(): ?string;

    public function getRefreshToken(): ?string;

    public function logout(?string $returnUri = null, ?array $params = null): string;

    public function clear(bool $transient = true): Auth0Interface;
}
