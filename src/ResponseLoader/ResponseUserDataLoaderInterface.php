<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\ResponseLoader;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\User\UserInterface;

interface ResponseUserDataLoaderInterface
{
    public function getIdentifierAuth0Side(): string;

    public function loadUserProperties(UserInterface $user, ParameterBag $userResponseData): UserInterface;

    public function checkUserProperties(UserInterface $user, ParameterBag $userResponseData): bool;

    public function checkUserIdentifier(string $identifier, ParameterBag $userResponseData): bool;
}
