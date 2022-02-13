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

class FunctionalProtectedAreaEntityProviderTest extends FunctionalTestCase
{
    protected static array $kernelOptions = [
        'security' => 'security_entity_provider.yaml'
    ];

    public function testProtectedPageNewUserFollowRedirects(): void
    {
        $client = static::createClient();
        $client->followRedirects(true);
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

        $crawler = $client->request('GET', '/protected_area');
        $this->assertSame('http://localhost/protected_area', $crawler->getUri());
        $this->assertSame('This is a protected page', $crawler->filterXPath('//h1')->text());

        if ($this->newSecuritySystem) {
            $this->assertInstanceOf(Auth0Token::class, $tokenStorage->getToken());
        } else {
            $this->assertInstanceOf(Auth0GuardToken::class, $tokenStorage->getToken());
        }
        $this->assertInstanceOf(User::class, $tokenStorage->getToken()->getUser());
        $this->assertTrue($authorizationChecker->isGranted('ROLE_USER'));

        $connector = $container->get('rmlev_auth0_login.connector_auth0.auth0wrapper');
        $userData = $connector->getUser();
        /** @var User $user */
        $user = $tokenStorage->getToken()->getUser();
        $this->checkParameterBagWithUser($userData, $user);

        $client->getKernel()->shutdown();
    }

    public function testProtectedPageExistingUserFollowRedirects(): void
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

        $tokenStorage = $container->get('security.token_storage');
        $this->assertNull($tokenStorage->getToken());
        $authorizationChecker = $container->get('security.authorization_checker');
        if ($this->newSecuritySystem) {
            $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));
        }

        $crawler = $client->request('GET', '/protected_area');
        $this->assertSame('http://localhost/protected_area', $crawler->getUri());
        $this->assertSame('This is a protected page', $crawler->filterXPath('//h1')->text());

        if ($this->newSecuritySystem) {
            $this->assertInstanceOf(Auth0Token::class, $tokenStorage->getToken());
        } else {
            $this->assertInstanceOf(Auth0GuardToken::class, $tokenStorage->getToken());
        }
        $this->assertInstanceOf(User::class, $tokenStorage->getToken()->getUser());
        $this->assertTrue($authorizationChecker->isGranted('ROLE_USER'));

        $this->assertEquals($user, $tokenStorage->getToken()->getUser());

        $client->getKernel()->shutdown();
    }
}
