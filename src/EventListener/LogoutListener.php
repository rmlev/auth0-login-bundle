<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\EventListener;

use Rmlev\Auth0LoginBundle\Helper\Auth0LogoutHandler;
use Rmlev\Auth0LoginBundle\Security\Guard\Token\Auth0GuardToken;
use Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Token\Auth0Token;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class LogoutListener
{
    private Auth0LogoutHandler $logoutHandler;

    public function __construct(Auth0LogoutHandler $logoutHandler)
    {
        if (!class_exists(LogoutEvent::class)) {
            throw new \Exception('You can\'t use LogoutListener prior to Symfony 5.1. Use logout success handler instead');
        }
        $this->logoutHandler = $logoutHandler;
    }

    public function onLogoutEvent(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $isAuth0Token = ($token instanceof Auth0Token);
        if ($token instanceof Auth0GuardToken) {
            $isAuth0Token = true;
        }

        if ($isAuth0Token) {
            $response = $this->logoutHandler->onLogout();
        } else {
            $response = new RedirectResponse('/');
        }
        $event->setResponse($response);
    }
}
