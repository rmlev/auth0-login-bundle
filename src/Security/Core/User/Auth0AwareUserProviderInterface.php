<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Security\Core\User;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

interface Auth0AwareUserProviderInterface extends UserProviderInterface
{
    /**
     * @param ParameterBag $userData User data returned by Auth0 SDK
     * @return string
     */
    public function getUserIdentifier(ParameterBag $userData): string;

    public function loadUserByIdentifierFromAuth0Response(string $identifier): ?UserInterface;

    public function loadUserFromAuth0Response(ParameterBag $userData): ?UserInterface;
}
