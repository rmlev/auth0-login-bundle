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

namespace Rmlev\Auth0LoginBundle\Tests\Connector\Auth0\Factory;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Factory\Auth0Factory;
use Auth0\SDK\Auth0;

final class Auth0FactoryTest extends TestCase
{
    public function testGetAuth0(): void
    {
        $auth0Factory = new Auth0Factory(
            'test.domain.com',
            'test.client.id',
            'test.client.secret',
            'c697833c829a334ec89e92b82b239738952c0b48e98328d38ff421ec50627681'
        );

        $auth0 = $auth0Factory->getAuth0();

        $this->assertInstanceOf(Auth0::class, $auth0);
    }
}
