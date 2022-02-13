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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0User;
use Rmlev\Auth0LoginBundle\Tests\Fixtures\Entity\User;
use Rmlev\Auth0LoginBundle\Tests\Functional\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class FunctionalTestCase extends WebTestCase
{
    const DEBUG = false;

    protected bool $newSecuritySystem = true;
    protected bool $useForward = false;

    protected static array $kernelOptions = [
        'security' => 'security.yaml',
        'success' => true,
        'extra' => [],
    ];

    protected static function createKernel(array $options = []): KernelInterface
    {
        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }

        if (isset($options['environment'])) {
            $env = $options['environment'];
        } elseif (isset($_ENV['APP_ENV'])) {
            $env = $_ENV['APP_ENV'];
        } elseif (isset($_SERVER['APP_ENV'])) {
            $env = $_SERVER['APP_ENV'];
        } else {
            $env = 'test';
        }

        if (isset($options['debug'])) {
            $debug = $options['debug'];
        } elseif (isset($_ENV['APP_DEBUG'])) {
            $debug = $_ENV['APP_DEBUG'];
        } elseif (isset($_SERVER['APP_DEBUG'])) {
            $debug = $_SERVER['APP_DEBUG'];
        } else {
            $debug = self::DEBUG;
        }
        $options = array_merge(self::$kernelOptions, static::$kernelOptions);

        return new static::$class($env, $debug, $options);
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::clearCache();
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (self::isNewSecuritySystem() === false) {
            $this->newSecuritySystem = false;
        }

    }

    protected function authenticationFailure(KernelBrowser $client, string $authUri): Response
    {
        $container = $this->getContainer();

        $tokenStorage = $container->get('security.token_storage');
        $this->assertNull($tokenStorage->getToken());
        $authorizationChecker = $container->get('security.authorization_checker');
        if ($this->newSecuritySystem) {
            $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));
        }

        $client->request('GET', $authUri);

        $session = $client->getRequest()->getSession();
        $this->assertInstanceOf(AuthenticationException::class, $session->get(Security::AUTHENTICATION_ERROR));

        $this->assertNull($tokenStorage->getToken());

        return $client->getResponse();
    }

    protected function authenticationFailureFollowRedirects(KernelBrowser $client, string $authUri): Response
    {
        $container = $this->getContainer();

        $tokenStorage = $container->get('security.token_storage');
        $this->assertNull($tokenStorage->getToken());
        $authorizationChecker = $container->get('security.authorization_checker');
        if ($this->newSecuritySystem) {
            $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));
        }

        $client->request('GET', $authUri);

        $session = $client->getRequest()->getSession();
        $this->assertInstanceOf(AuthenticationException::class, $session->get(Security::AUTHENTICATION_ERROR));

        if ($this->newSecuritySystem) {
            $this->assertNull($tokenStorage->getToken());
        } else {
            /** @phpstan-ignore-next-line  */
            $this->assertInstanceOf(AnonymousToken::class, $tokenStorage->getToken());
        }
        $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));

        return $client->getResponse();
    }

    protected function authenticationSuccessFollowRedirects(KernelBrowser $client, string $authUri): Response
    {
        $container = $this->getContainer();

        $tokenStorage = $container->get('security.token_storage');
        $this->assertNull($tokenStorage->getToken());
        $authorizationChecker = $container->get('security.authorization_checker');
        if ($this->newSecuritySystem) {
            $this->assertFalse($authorizationChecker->isGranted('ROLE_USER'));
        }

        $client->request('GET', $authUri);

        $session = $client->getRequest()->getSession();
        $this->assertNull($session->get(Security::AUTHENTICATION_ERROR));

        $this->assertInstanceOf(AbstractToken::class, $tokenStorage->getToken());
        $this->assertInstanceOf(UserInterface::class, $tokenStorage->getToken()->getUser());
        $this->assertTrue($authorizationChecker->isGranted('ROLE_USER'));

        return $client->getResponse();
    }

    /**
     * @param User|Auth0User $user
     * @param array $userData
     */
    protected function checkUserWithUserData(UserInterface $user, array $userData): void
    {
        $this->assertSame($user->getEmail(), $userData['email']);
        $this->assertSame($user->getNickname(), $userData['nickname']);
        $this->assertSame($user->getName(), $userData['name']);
        $this->assertSame($user->getAuth0UserKey(), $userData['auth0_key']);
        $this->assertSame($user->getAvatar(), $userData['avatar']);
    }

    protected function checkParameterBagWithUserData(ParameterBag $user, array $userData): void
    {
        $this->assertSame($user->get('email'), $userData['email']);
        $this->assertSame($user->get('nickname'), $userData['nickname']);
        $this->assertSame($user->get('name'), $userData['name']);
        $this->assertSame($user->get('sub'), $userData['auth0_key']);
        $this->assertSame($user->get('picture'), $userData['avatar']);
    }

    /**
     * @param ParameterBag $userParameters
     * @param User|Auth0User $user
     */
    protected function checkParameterBagWithUser(ParameterBag $userParameters, UserInterface $user): void
    {
        $this->assertSame($userParameters->get('email'), $user->getEmail());
        $this->assertSame($userParameters->get('nickname'), $user->getNickname());
        $this->assertSame($userParameters->get('name'), $user->getName());
        $this->assertSame($userParameters->get('sub'), $user->getAuth0UserKey());
        $this->assertSame($userParameters->get('picture'), $user->getAvatar());
    }

    protected function createSchema(EntityManager $entityManager): void
    {
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema([
            $entityManager->getClassMetadata(User::class),
        ]);
    }

    protected static function getContainer(): ContainerInterface
    {
        if (method_exists(KernelTestCase::class, 'getContainer')) {
            /** @phpstan-ignore-next-line  */
            return parent::getContainer();
        }

        /** @phpstan-ignore-next-line  */
        return self::$container;
    }

    private static function clearCache(): void
    {
        $kernel = new AppKernel('test', true);
        $cacheDir = $kernel->getCacheDir();

        (new Filesystem())->remove($cacheDir);
    }

    protected static function isNewSecuritySystem(): bool
    {
        return interface_exists(AuthenticatorFactoryInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        self::clearCache();
    }
}
