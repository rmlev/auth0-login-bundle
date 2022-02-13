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

namespace Rmlev\Auth0LoginBundle\Tests\DependencyInjection\Security\Factory;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0SecurityFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0UserProviderFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\SecurityNodeConfigurator;

final class Auth0SecurityFactoryPositionTest extends TestCase
{
    public function testGetPosition(): void
    {
        $nodeConfigurator = new SecurityNodeConfigurator();
        $userProviderFactory = new Auth0UserProviderFactory();
        $securityFactory = new Auth0SecurityFactory($nodeConfigurator, $userProviderFactory);

        $this->assertSame('http', $securityFactory->getPosition());
    }
}
