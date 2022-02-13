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

namespace Rmlev\Auth0LoginBundle\Tests\Functional;

class FunctionalFailureTest extends FunctionalTestCase
{
    protected static array $kernelOptions = [
        'success' => false,
    ];

    public function testAuthenticationFollowRedirects(): void
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->disableReboot();

        $container = self::getContainer();

        $router = $container->get('router.default');
        $response = $this->authenticationFailureFollowRedirects($client, $router->generate('auth0_authorize'));
        $this->assertSame('Authentication failure', $response->getContent());

        $client->getKernel()->shutdown();
    }
}
