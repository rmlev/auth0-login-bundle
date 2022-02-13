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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FunctionalFailureEventTest extends FunctionalTestCase
{
    protected static array $kernelOptions = [
        'success' => false,
        'extra' => ['failure_listener.yaml']
    ];

    public function testAuthentication(): void
    {
        $client = static::createClient();

        $container = self::getContainer();

        /** @var RedirectResponse $response */
        $response = $this->authenticationFailure($client, '/auth0/callback?code=stub');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $router = $container->get('router.default');
        $this->assertSame($router->generate('app_event_failure', [], UrlGeneratorInterface::ABSOLUTE_URL), $response->getTargetUrl());
    }

    public function testAuthenticationFollowRedirects(): void
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->disableReboot();

        $container = self::getContainer();

        $router = $container->get('router.default');
        $response = $this->authenticationFailureFollowRedirects($client, $router->generate('auth0_authorize'));
        $this->assertSame('This is redirect on failure event', $response->getContent());

        $client->getKernel()->shutdown();
    }
}
