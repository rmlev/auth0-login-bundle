<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Event;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ConnectSuccessEvent extends Event
{
    private TokenInterface $authenticatedToken;
    private Request $request;
    private string $firewallName;
    private ?RedirectResponse $response = null;

    public function __construct(TokenInterface $authenticatedToken, Request $request, string $firewallName)
    {
        $this->authenticatedToken = $authenticatedToken;
        $this->request = $request;
        $this->firewallName = $firewallName;
    }

    public function getAuthenticatedToken(): TokenInterface
    {
        return $this->authenticatedToken;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }

    public function getResponse(): ?RedirectResponse
    {
        return $this->response;
    }

    public function setResponse(RedirectResponse $response): void
    {
        $this->response = $response;
    }
}
