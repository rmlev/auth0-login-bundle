<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Security\Core\Logout;

use Rmlev\Auth0LoginBundle\Helper\Auth0LogoutHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

final class Auth0LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    private Auth0LogoutHandler $logoutHandler;

    public function __construct(Auth0LogoutHandler $logoutHandler)
    {
        $this->logoutHandler = $logoutHandler;
    }

    /**
     * @inheritDoc
     */
    public function onLogoutSuccess(Request $request)
    {
        return $this->logoutHandler->onLogout();
    }
}
