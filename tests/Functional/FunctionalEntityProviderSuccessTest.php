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

use Rmlev\Auth0LoginBundle\Security\Guard\Token\Auth0GuardToken;
use Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Token\Auth0Token;
use Rmlev\Auth0LoginBundle\Tests\Fixtures\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FunctionalEntityProviderSuccessTest extends FunctionalTestCase
{
    protected static array $kernelOptions = [
        'security' => 'security_entity_provider.yaml'
    ];

    public function testAuthenticateNewUserFollowRedirects(): void
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->disableReboot();

        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();
        $this->createSchema($em);

        $response = $this->authenticationSuccessFollowRedirects($client, '/auth0/callback?code=stub');

        $connector = $container->get('rmlev_auth0_login.connector_auth0.auth0wrapper');

        $responseData = json_decode($response->getContent(), true);
        $this->assertSame('This is the successful authentication page', $responseData['message']);
        $this->checkParameterBagWithUserData($connector->getUser(), $responseData['user']);

        $tokenStorage = $container->get('security.token_storage');
        if ($this->newSecuritySystem) {
            $this->assertInstanceOf(Auth0Token::class, $tokenStorage->getToken());
        } else {
            $this->assertInstanceOf(Auth0GuardToken::class, $tokenStorage->getToken());
        }
        $this->assertInstanceOf(User::class, $tokenStorage->getToken()->getUser());

        // Go to homepage
        $crawler = $client->request('GET', '/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        if ($this->newSecuritySystem) {
            $this->assertSame(Auth0Token::class, $responseData['token']);
        } else {
            $this->assertSame(Auth0GuardToken::class, $responseData['token']);
        }
        /** @var User $user */
        $user = $tokenStorage->getToken()->getUser();
        $this->checkUserWithUserData($user, $responseData['user']);

        $client->getKernel()->shutdown();
    }

    public function testAuthenticateNewUser(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();
        $this->createSchema($em);

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
        $this->assertInstanceOf(User::class, $tokenStorage->getToken()->getUser());
        $this->assertTrue($authorizationChecker->isGranted('ROLE_USER'));

        // Go to homepage
        $crawler = $client->request('GET', '/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        if ($this->newSecuritySystem) {
            $this->assertSame(Auth0Token::class, $responseData['token']);
        } else {
            $this->assertSame(Auth0GuardToken::class, $responseData['token']);
        }
        /** @var User $user */
        $user = $tokenStorage->getToken()->getUser();
        $this->checkUserWithUserData($user, $responseData['user']);

        $client->getKernel()->shutdown();
    }

    public function testAuthenticateExistingUserFollowRedirects(): void
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->disableReboot();

        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();
        $this->createSchema($em);

        $connector = $container->get('rmlev_auth0_login.connector_auth0.auth0wrapper');
        $user = new User();
        $user->setEmail($connector->getUser()->get('email'));
        $user->setNickname($connector->getUser()->get('nickname'));
        $user->setName($connector->getUser()->get('name'));
        $user->setAuth0UserKey($connector->getUser()->get('sub'));
        $user->setAvatar($connector->getUser()->get('picture'));
        $em->persist($user);
        $em->flush();

        $response = $this->authenticationSuccessFollowRedirects($client, '/auth0/callback?code=stub');

        $responseData = json_decode($response->getContent(), true);
        $this->assertSame('This is the successful authentication page', $responseData['message']);
        $this->checkParameterBagWithUserData($connector->getUser(), $responseData['user']);

        $tokenStorage = $container->get('security.token_storage');
        if ($this->newSecuritySystem) {
            $this->assertInstanceOf(Auth0Token::class, $tokenStorage->getToken());
        } else {
            $this->assertInstanceOf(Auth0GuardToken::class, $tokenStorage->getToken());
        }
        $this->assertInstanceOf(User::class, $tokenStorage->getToken()->getUser());

        $this->assertEquals($user, $tokenStorage->getToken()->getUser());

        // Go to homepage
        $crawler = $client->request('GET', '/');
        $this->assertSame('http://localhost/', $crawler->getUri());

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        if ($this->newSecuritySystem) {
            $this->assertSame(Auth0Token::class, $responseData['token']);
        } else {
            $this->assertSame(Auth0GuardToken::class, $responseData['token']);
        }
        $this->checkUserWithUserData($user, $responseData['user']);

        $client->getKernel()->shutdown();
    }
}
