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

class FunctionalSuccessEventTest extends FunctionalTestCase
{
    protected static array $kernelOptions = [
        'extra' => ['success_listener.yaml'],
    ];

    public function testAuthenticate(): void
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
        $this->assertSame('http://localhost/success_event', $response->getTargetUrl());

        $crawler = $client->request('GET', $response->getTargetUrl());

        $this->assertSame('http://localhost/success_event', $crawler->getUri());
        $connector = $container->get('rmlev_auth0_login.connector_auth0.auth0wrapper');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('This is redirect on success event', $responseData['message']);
        $userData = $responseData['user'];
        $this->checkParameterBagWithUserData($connector->getUser(), $userData);

        if ($this->newSecuritySystem) {
            $this->assertSame(Auth0Token::class, $responseData['token']);
            $this->assertInstanceOf(Auth0Token::class, $tokenStorage->getToken());
        } else {
            $this->assertSame(Auth0GuardToken::class, $responseData['token']);
            $this->assertInstanceOf(Auth0GuardToken::class, $tokenStorage->getToken());
        }
        $this->assertInstanceOf(Auth0User::class, $tokenStorage->getToken()->getUser());
        $this->assertTrue($authorizationChecker->isGranted('ROLE_USER'));
    }
}
