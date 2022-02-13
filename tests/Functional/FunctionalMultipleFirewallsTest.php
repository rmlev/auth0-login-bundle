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
use Rmlev\Auth0LoginBundle\Tests\Fixtures\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class FunctionalMultipleFirewallsTest extends FunctionalTestCase
{
    protected static array $kernelOptions = [
        'security' => 'security_multiple_firewalls.yaml'
    ];

    /**
     * @dataProvider providerLoginFollowRedirects
     */
    public function testLoginFollowRedirects(string $firewallName, bool $entityUser): void
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->disableReboot();

        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();
        $this->createSchema($em);

        $container = self::getContainer();

        $tokenStorage = $container->get('security.token_storage');
        $this->assertNull($tokenStorage->getToken());
        $authorizationChecker = $container->get('security.authorization_checker');
        if ($this->newSecuritySystem) {
            $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));
        }

        $router = $container->get('router.default');
        $client->request('GET', $router->generate('auth0_authorize', ['firewall' => $firewallName]));

        $session = $client->getRequest()->getSession();
        $this->assertNull($session->get(Security::AUTHENTICATION_ERROR));

        $this->assertInstanceOf(AbstractToken::class, $tokenStorage->getToken());
        $this->assertInstanceOf(UserInterface::class, $tokenStorage->getToken()->getUser());
        $this->assertTrue($authorizationChecker->isGranted('ROLE_USER'));

        $tokenStorage = $container->get('security.token_storage');
        if ($this->newSecuritySystem) {
            $this->assertInstanceOf(Auth0Token::class, $tokenStorage->getToken());
        } else {
            $this->assertInstanceOf(Auth0GuardToken::class, $tokenStorage->getToken());
        }
        if ($entityUser) {
            $this->assertInstanceOf(User::class, $tokenStorage->getToken()->getUser());
        } else {
            $this->assertInstanceOf(Auth0User::class, $tokenStorage->getToken()->getUser());
        }

        $response = $client->getResponse();
        $this->assertSame(
            sprintf('This is redirect on success login for "%s" firewall', $firewallName),
            $response->getContent()
        );

        $client->request('GET', $router->generate($firewallName.'_logout'));
        $response = $client->getResponse();
        $this->assertSame(sprintf('This is redirect on logout for "%s" firewall', $firewallName), $response->getContent());

        if ($this->newSecuritySystem) {
            $this->assertNull($tokenStorage->getToken());
        } else {
            $this->assertInstanceOf(AnonymousToken::class, $tokenStorage->getToken());
        }
        if ($this->newSecuritySystem) {
            $authorizationChecker = $container->get('security.authorization_checker');
            $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));
        }

        $client->getKernel()->shutdown();
    }

    public function providerLoginFollowRedirects(): \Generator
    {
        yield ['stub1', false];
        yield ['stub2', true];
    }
}
