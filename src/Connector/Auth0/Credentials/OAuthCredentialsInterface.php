<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials;

use Symfony\Component\HttpFoundation\ParameterBag;

interface OAuthCredentialsInterface
{
    public function getUserData(): ParameterBag;

    public function getIdToken(): ?string;

    public function getAccessToken(): ?string;

    public function getAccessTokenScope(): ParameterBag;

    public function getAccessTokenExpiration(): int;

    public function isAccessTokenExpired(): bool;

    public function getRefreshToken(): ?string;

    public function getTokenAttributes(): ParameterBag;
}
