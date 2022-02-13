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

use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0User;
use Rmlev\Auth0LoginBundle\Security\Guard\Token\Auth0GuardToken;
use Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Token\Auth0Token;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class FunctionalSuccessTest extends FunctionalTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertSame('http://localhost/', $crawler->getUri());
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('This is the homepage', $responseData['message']);
        if ($this->newSecuritySystem) {
            $this->assertNull($responseData['token']);
            $this->assertNull($responseData['user']);
        } else {
            $this->assertSame(AnonymousToken::class, $responseData['token']);
            $this->assertSame('anon.', $responseData['user']);
        }
    }

    public function testAuthentication(): void
    {
        $client = static::createClient();

        $container = self::getContainer();

        $tokenStorage = $container->get('security.token_storage');
        $this->assertNull($tokenStorage->getToken());
        $authorizationChecker = $container->get('security.authorization_checker');
        if ($this->newSecuritySystem) {
            $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));
        }

        $crawler = $client->request('GET', '/auth0/callback?code=stub');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertInstanceOf(RedirectResponse::class, $client->getResponse());
        /** @var RedirectResponse $response */
        $response = $client->getResponse();
        $this->assertSame('http://localhost/success_path', $response->getTargetUrl());

        $crawler = $client->request('GET', $response->getTargetUrl());

        $this->assertSame('http://localhost/success_path', $crawler->getUri());
        $connector = $container->get('rmlev_auth0_login.connector_auth0.auth0wrapper');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('This is the successful authentication page', $responseData['message']);
        $this->checkParameterBagWithUserData($connector->getUser(), $responseData['user']);

        if ($this->newSecuritySystem) {
            $this->assertInstanceOf(Auth0Token::class, $tokenStorage->getToken());
        } else {
            $this->assertInstanceOf(Auth0GuardToken::class, $tokenStorage->getToken());
        }
        $this->assertInstanceOf(Auth0User::class, $tokenStorage->getToken()->getUser());
        $this->assertTrue($authorizationChecker->isGranted('ROLE_USER'));
    }

    public function testLoginAuthenticationFollowRedirects(): void
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->disableReboot();

        $container = self::getContainer();

        $router = $container->get('router.default');
        $response = $this->authenticationSuccessFollowRedirects($client, $router->generate('auth0_authorize'));

        $tokenStorage = $container->get('security.token_storage');
        if ($this->newSecuritySystem) {
            $this->assertInstanceOf(Auth0Token::class, $tokenStorage->getToken());
        } else {
            $this->assertInstanceOf(Auth0GuardToken::class, $tokenStorage->getToken());
        }
        $this->assertInstanceOf(Auth0User::class, $tokenStorage->getToken()->getUser());

        $responseData = json_decode($response->getContent(), true);
        $this->assertSame('This is the successful authentication page', $responseData['message']);
        $connector = $container->get('rmlev_auth0_login.connector_auth0.auth0wrapper');
        /** @var Auth0User $user */
        $user = $tokenStorage->getToken()->getUser();
        $this->checkParameterBagWithUser($connector->getUser(), $user);
        $this->checkParameterBagWithUserData($connector->getUser(), $responseData['user']);

        $client->getKernel()->shutdown();
    }

    public function testLogoutFollowRedirects(): void
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->disableReboot();

        $container = self::getContainer();

        $router = $container->get('router.default');
        $this->authenticationSuccessFollowRedirects($client, $router->generate('auth0_authorize'));

        $tokenStorage = $container->get('security.token_storage');
        if ($this->newSecuritySystem) {
            $this->assertInstanceOf(Auth0Token::class, $tokenStorage->getToken());
        } else {
            $this->assertInstanceOf(Auth0GuardToken::class, $tokenStorage->getToken());
        }
        $this->assertInstanceOf(Auth0User::class, $tokenStorage->getToken()->getUser());

        $client->request('GET', $router->generate('app_logout'));
        $response = $client->getResponse();
        $this->assertSame('This is redirect on success logout', $response->getContent());

        if ($this->newSecuritySystem) {
            $this->assertNull($tokenStorage->getToken());
        } else {
            $this->assertInstanceOf(AnonymousToken::class, $tokenStorage->getToken());
        }
        $authorizationChecker = $container->get('security.authorization_checker');
        $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));

        $client->getKernel()->shutdown();
    }
}
