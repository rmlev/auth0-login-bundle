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
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\User\UserInterface;

class FunctionalProtectedAreaTest extends FunctionalTestCase
{
    public function testEntryPointCallOnProtectedPage(): void
    {
        $client = static::createClient();

        $container = self::getContainer();

        $tokenStorage = $container->get('security.token_storage');
        $this->assertNull($tokenStorage->getToken());
        $authorizationChecker = $container->get('security.authorization_checker');
        if ($this->newSecuritySystem) {
            $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));
        }

        $client->request('GET', '/protected_area');

        $session = $client->getRequest()->getSession();
        $this->assertSame('http://localhost/protected_area', $session->get('_security.stub.target_path'));

        /** @var RedirectResponse $response */
        $response = $client->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        if (!$this->useForward) {
            $this->assertSame('http://localhost/auth0/start', $response->getTargetUrl());
        } else {
            $this->assertSame('/auth0_server_stub?redirect_url=http://localhost/auth0/callback', $response->getTargetUrl());
        }

        if ($this->newSecuritySystem) {
            $this->assertNull($tokenStorage->getToken());
        } else {
            /** @phpstan-ignore-next-line  */
            $this->assertInstanceOf(AnonymousToken::class, $tokenStorage->getToken());
        }
        $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));
    }

    public function testProtectedPageFollowRedirects(): void
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->disableReboot();

        $container = self::getContainer();

        $tokenStorage = $container->get('security.token_storage');
        $this->assertNull($tokenStorage->getToken());
        $authorizationChecker = $container->get('security.authorization_checker');
        if ($this->newSecuritySystem) {
            $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));
        }

        $crawler = $client->request('GET', '/protected_area');
        $this->assertSame('http://localhost/protected_area', $crawler->getUri());
        $this->assertSame('This is a protected page', $crawler->filterXPath('//h1')->text());

        $this->assertInstanceOf(AbstractToken::class, $tokenStorage->getToken());
        $this->assertInstanceOf(UserInterface::class, $tokenStorage->getToken()->getUser());
        $this->assertTrue($authorizationChecker->isGranted('ROLE_USER'));

        $client->getKernel()->shutdown();
    }
}
