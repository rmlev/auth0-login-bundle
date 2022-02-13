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
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\EventDispatcher\Event;

final class ConnectFailureEvent extends Event
{
    private Request $request;
    private AuthenticationException $exception;
    private ?RedirectResponse $response = null;

    public function __construct(Request $request, AuthenticationException $exception)
    {
        $this->request = $request;
        $this->exception = $exception;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getException(): AuthenticationException
    {
        return $this->exception;
    }

    public function getResponse(): ?RedirectResponse
    {
        return $this->response;
    }

    public function setResponse(?RedirectResponse $response): void
    {
        $this->response = $response;
    }
}
