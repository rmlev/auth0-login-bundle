<?php

declare(strict_types=1);

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Tests\Functional\App\src\Connector\Auth0;

use Auth0\SDK\Contract\Auth0Interface;
use Auth0\SDK\Exception\StateException;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\OAuthCredentialsInterface;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

final class StubFailureConnector implements ConnectorInterface
{

    public function login(?string $redirectUrl = null, ?array $params = null): string
    {
        return '/auth0_server_stub?redirect_url='.$redirectUrl;
    }

    public function exchange(?string $redirectUri = null, ?string $code = null, ?string $state = null): bool
    {
        StateException::failedCodeExchange();
        return false;
    }

    public function getCredentials(): ?OAuthCredentialsInterface
    {
        return null;
    }

    public function getUser(): ?ParameterBag
    {
        return null;
    }

    public function getIdToken(): ?string
    {
        return null;
    }

    public function getAccessToken(): ?string
    {
        return null;
    }

    public function getRefreshToken(): ?string
    {
        return null;
    }

    public function logout(?string $returnUri = null, ?array $params = null): string
    {
        return '/';
    }

    public function clear(bool $transient = true): Auth0Interface
    {
        $testAuth0Factory = new StubAuth0Factory();
        return $testAuth0Factory->createAuth0Stub();
    }
}
