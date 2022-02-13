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

namespace Rmlev\Auth0LoginBundle\Tests\Security\Core\User;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\Auth0Credentials;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\Helper\Auth0Options;
use Rmlev\Auth0LoginBundle\Helper\Auth0OptionsCollection;
use Rmlev\Auth0LoginBundle\ResponseLoader\ResponseUserDataLoader;
use Rmlev\Auth0LoginBundle\Security\Core\User\EntityUserProvider;
use Rmlev\Auth0LoginBundle\Tests\Fixtures\Entity\User;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;

final class EntityUserProviderTest extends TestCase
{
    public function testLoadUserByIdentifier(): void
    {
        $em = $this->createTestEntityManager();
        $this->createSchema($em);

        $user1 = new User();
        $user1->setEmail('stub1@example.com');
        $user1->setNickname('stub1_nickname');
        $user1->setName('stub1_name');
        $user1->setAuth0UserKey('stub1_key');

        $user2 = new User();
        $user2->setEmail('stub2@example.com');
        $user2->setNickname('stub2_nickname');
        $user2->setName('stub2_name');
        $user2->setAuth0UserKey('stub2_key');

        $em->persist($user1);
        $em->persist($user2);
        $em->flush();

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class,
            'email'
        );

        $this->assertSame($user1, $provider->loadUserByIdentifier('stub1@example.com'));
        $this->assertSame($user1, $provider->loadUserByUsername('stub1@example.com'));
    }

    public function testLoadUserNotFound(): void
    {
        $this->expectUserNotFoundException();

        $em = $this->createTestEntityManager();
        $this->createSchema($em);

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class,
            'email'
        );

        $provider->loadUserByIdentifier('stub1@example.com');
    }

    public function testLoadUserByIdentifierWithUserLoaderRepositoryAndWithoutProperty(): void
    {
        $user = new User();
        $user->setEmail('user1@example.com');

        $repository = $this->createMock(UserLoaderRepository::class);
        $repository
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('user1@example.com')
            ->willReturn($user);

        $em = $this->createMock(EntityManager::class);
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class
        );
        $this->assertSame($user, $provider->loadUserByIdentifier('user1@example.com'));
    }

    public function testLoadUserByUsernameWithUserLoaderRepositoryAndWithoutProperty(): void
    {
        $user = new User();
        $user->setEmail('user1@example.com');

        $repository = $this->createMock(UsernameLoaderRepository::class);
        $repository
            ->expects($this->any())
            ->method('loadUserByUsername')
            ->with('user1@example.com')
            ->willReturn($user);
        $repository
            ->expects($this->any())
            ->method('loadUserByIdentifier')
            ->with('user1@example.com')
            ->willReturn($user);

        $em = $this->createMock(EntityManager::class);
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class
        );
        $this->assertSame($user, $provider->loadUserByIdentifier('user1@example.com'));
    }

    public function testNotImplementUserLoaderInterfaceWithoutProperty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new User();
        $user->setEmail('user1@example.com');

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects($this->never())
            ->method('loadUserByIdentifier')
            ->with('user1@example.com')
            ->willReturn($user);

        $em = $this->createMock(EntityManager::class);
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class
        );
        $provider->loadUserByIdentifier('user1@example.com');
    }

    public function testRefreshUser(): void
    {
        $em = $this->createTestEntityManager();
        $this->createSchema($em);

        $user1 = new User();
        $user1->setEmail('stub1@example.com');
        $user1->setNickname('stub1_nickname');
        $user1->setName('stub1_name');
        $user1->setAuth0UserKey('stub1_key');

        $user2 = new User();
        $user2->setEmail('stub2@example.com');
        $user2->setNickname('stub2_nickname');
        $user2->setName('stub2_name');
        $user2->setAuth0UserKey('stub2_key');

        $em->persist($user1);
        $em->persist($user2);
        $em->flush();

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class,
            'email'
        );

        $user1->setEmail('stub2@example.com');
        $this->assertSame($user1, $provider->refreshUser($user1));
    }

    public function testRefreshUnsupportedUser(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $em = $this->createTestEntityManager();

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class,
            'email'
        );

        $userStub = $this->createStub(AbstractUser::class);

        $provider->refreshUser($userStub);
    }

    public function testRefreshUserWithInvalidIdentifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $em = $this->createTestEntityManager();

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class,
            'email'
        );

        $userStub = $this->createStub(User::class);

        $provider->refreshUser($userStub);
    }

    public function testRefreshUserWithUserProviderRepository(): void
    {
        $user = new User();

        $repository = $this->createMock(UserProviderRepository::class);
        $repository
            ->expects($this->any())
            ->method('refreshUser')
            ->with($user)
            ->willReturn($user);

        $em = $this->createMock(EntityManager::class);
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class,
            'email'
        );
        $this->assertSame($user, $provider->refreshUser($user));
    }

    public function testRefreshUserNotFound(): void
    {
        $this->expectUserNotFoundException();

        $em = $this->createTestEntityManager();
        $this->createSchema($em);

        $user = new User();
        $user->setEmail('stub1@example.com');
        $user->setNickname('stub1_nickname');
        $user->setName('stub1_name');
        $user->setAuth0UserKey('stub1_key');

        $em->persist($user);
        $em->flush();

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class,
            'email'
        );

        $user1 = clone $user;

        $em->remove($user);
        $em->flush();

        $provider->refreshUser($user1);
    }

    public function testSupportsClass(): void
    {
        $em = $this->createMock(EntityManager::class);

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class
        );

        $this->assertTrue($provider->supportsClass(User::class));
        $this->assertFalse($provider->supportsClass(AbstractUser::class));
    }

    public function testLoadUserByIdentifierShouldLoadUserWhenProperInterfaceProvided(): void
    {
        $repository = $this->createMock(UserLoaderRepository::class);
        $repository->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('stub@example.com')
            ->willReturn(
                $this->createMock(UserInterface::class)
            );

        $provider = new EntityUserProvider(
            $this->getManager($this->getObjectManager($repository)),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class
        );

        $provider->loadUserByIdentifier('stub@example.com');
    }

    public function testLoadUserByIdentifierShouldDeclineInvalidInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $repository = $this->createMock(ObjectRepository::class);

        $provider = new EntityUserProvider(
            $this->getManager($this->getObjectManager($repository)),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class
        );

        $provider->loadUserByIdentifier('stub@example.com');
    }

    /**
     * @dataProvider getUserIdentifierDataProvider
     */
    public function testGetUserIdentifier(array $userData, string $identifier, bool $ok): void
    {
        if (!$ok) {
            $this->expectUserNotFoundException();
        }

        $em = $this->createStub(ObjectManager::class);

        $provider = new EntityUserProvider(
            $this->getManager($em),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class,
            'email'
        );

        $this->assertSame($identifier, $provider->getUserIdentifier(new ParameterBag($userData)));
    }

    /**
     * @dataProvider getUserIdentifierDataProvider
     */
    public function testLoadUserByIdentifierFromAuth0Response(array $userData, string $identifier, bool $ok): void
    {
        if (!$ok) {
            $this->expectUserNotFoundException();
        }

        $repository = $this->createMock(ObjectRepository::class);

        $provider = new EntityUserProvider(
            $this->getManager($this->getObjectManager($repository)),
            $this->getConnectorWithUserData($userData),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class,
            'email'
        );

        $userDataBag = new ParameterBag($userData);
        /** @var User $user */
        $user = $provider->loadUserByIdentifierFromAuth0Response($identifier);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($userDataBag->get('email', ''), $user->getUserIdentifier());
        $this->assertSame($userDataBag->get('email', ''), $user->getEmail());
        $this->assertSame($userDataBag->get('nickname', ''), $user->getNickname());
        $this->assertSame($userDataBag->get('name', ''), $user->getName());
    }

    public function testLoadUserNotFoundByIdentifierFromAuth0Response(): void
    {
        $this->expectUserNotFoundException();

        $repository = $this->createMock(ObjectRepository::class);

        $provider = new EntityUserProvider(
            $this->getManager($this->getObjectManager($repository)),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class,
            'email'
        );

        $provider->loadUserByIdentifierFromAuth0Response('stub@example.com');
    }

    public function getUserIdentifierDataProvider(): \Generator
    {
        yield [
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            'test@test.mail.com',
            true,
        ];

        yield [
            [
                'nickname' => 'stub',
                'name' => 'stub@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'stub@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.123',
            ],
            'stub@test.mail.com',
            true,
        ];

        yield [
            [
                'nickname' => 'stub',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'stub@test.mail.com',
                'email_verified' => true,
                'sub' => 'abc.123',
            ],
            'stub@test.mail.com',
            true,
        ];

        yield [
            [
                'nickname' => 'stub',
                'name' => 'stub@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email_verified' => true,
                'sub' => 'abc.123',
            ],
            'stub@test.mail.com',
            false,
        ];
    }

    /**
     * @dataProvider loadUserDataProvider
     */
    public function testLoadUserFromAuth0Response(array $userData, bool $ok): void
    {
        if (!$ok) {
            $this->expectUserNotFoundException();
        }

        $userDataBag = new ParameterBag($userData);
        $user = new User();
        if ($userDataBag->has('email')) {
            $user->setEmail($userDataBag->get('email'));
        }
        if ($userDataBag->has('nickname')) {
            $user->setNickname($userDataBag->get('nickname'));
        }
        if ($userDataBag->has('name')) {
            $user->setName($userDataBag->get('name'));
        }

        $repository = $this->createMock(UserLoaderRepository::class);
        $repository->expects($this->any())
            ->method('loadUserByIdentifier')
            ->willReturn(
                $user
            );

        $provider = new EntityUserProvider(
            $this->getManager($this->getObjectManager($repository)),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class
        );

        /** @var User $user */
        $user = $provider->loadUserFromAuth0Response(new ParameterBag($userData));

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($userDataBag->get('email'), $user->getUserIdentifier());
        $this->assertSame($userDataBag->get('email'), $user->getEmail());
        $this->assertSame($userDataBag->get('nickname'), $user->getNickname());
        $this->assertSame($userDataBag->get('name'), $user->getName());
    }

    /**
     * @dataProvider loadUserDataProvider
     */
    public function testLoadNewUserFromAuth0Response(array $userData, bool $ok): void
    {
        if (!$ok) {
            $this->expectUserNotFoundException();
        }

        $repository = $this->createMock(ObjectRepository::class);

        $provider = new EntityUserProvider(
            $this->getManager($this->getObjectManager($repository)),
            $this->getConnector(),
            $this->getAuth0Helper(),
            $this->getResponseUserDataLoader(),
            User::class,
            'email'
        );
        $userDataBag = new ParameterBag($userData);

        /** @var User $user */
        $user = $provider->loadUserFromAuth0Response(new ParameterBag($userData));

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($userDataBag->get('email'), $user->getUserIdentifier());
        $this->assertSame($userDataBag->get('email'), $user->getEmail());
        $this->assertSame($userDataBag->get('nickname'), $user->getNickname());
        $this->assertSame($userDataBag->get('name'), $user->getName());
    }

    public function loadUserDataProvider(): \Generator
    {
        yield [
            [
                'email' => 'test@example.com',
                'nickname' => 'nickname_test',
                'name' => 'name_test',
            ],
            true,
        ];

        yield [
            [
                'email' => 'stub@example.com',
                'nickname' => 'nickname_stub',
                'name' => 'name_stub',
            ],
            true,
        ];

        yield [
            [
                'nickname' => 'nickname_stub',
                'name' => 'name_stub',
            ],
            false,
        ];
    }

    private function createTestEntityManager(Configuration $config = null): EntityManager
    {
        if (!extension_loaded('pdo_sqlite')) {
            TestCase::markTestSkipped('Extension pdo_sqlite is required.');
        }

        $params = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        if ($config === null) {
            $config = new Configuration();
            $config->setEntityNamespaces(['EntityTestsDoctrine' => 'Rmlev\Auth0LoginBundle\Tests\Entity']);
            $config->setAutoGenerateProxyClasses(true);
            $config->setProxyDir(sys_get_temp_dir());
            $config->setProxyNamespace('EntityTests\Doctrine');
            $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        }

        return EntityManager::create($params, $config);
    }

    private function createSchema(EntityManager $em): void
    {
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema([
            $em->getClassMetadata(User::class),
        ]);
    }

    /**
     * @param ObjectRepository<User> $repository
     * @return ObjectManager
     */
    private function getObjectManager(ObjectRepository $repository): ObjectManager
    {
        $em = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['getClassMetadata', 'getRepository'])
            ->getMockForAbstractClass();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        return $em;
    }

    private function getManager(ObjectManager $em, string $name = null): ManagerRegistry
    {
        $manager = $this->createMock(ManagerRegistry::class);
        $manager->expects($this->any())
            ->method('getManager')
            ->with($this->equalTo($name))
            ->willReturn($em);

        return $manager;
    }

    private function getConnector(): ConnectorInterface
    {
        return $this->createStub(ConnectorInterface::class);
    }

    private function getConnectorWithUserData(array $userData): ConnectorInterface
    {
        $credentials = new Auth0Credentials(
            $userData,
            'idToken.stub',
            'accessToken.stub',
            ['email', 'profile'],
            time() + 100,
            false
        );

        $connector = $this->createMock(ConnectorInterface::class);
        $connector->expects($this->any())
            ->method('getCredentials')
            ->willReturn($credentials);

        return $connector;
    }

    private function getAuth0Helper(): Auth0Helper
    {
        $firewallMapStab = $this->createStub(FirewallMap::class);

        $options = [
            'check_path' => '/stub_callback',
            'use_forward' => false,
            'require_previous_session' => false,
            'login_path' => '/login',
        ];

        $auth0Options = new Auth0Options($options);
        $auth0OptionsCollection = new Auth0OptionsCollection();
        $auth0OptionsCollection->addOptions($auth0Options, 'stub');

        return new Auth0Helper($firewallMapStab, new HttpUtils(), new RequestStack(), $auth0OptionsCollection);
    }

    private function getResponseUserDataLoader(): ResponseUserDataLoader
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        return new ResponseUserDataLoader($propertyAccessor);
    }

    private function expectUserNotFoundException(): void
    {
        if (class_exists(UserNotFoundException::class)) {
            /** @phpstan-ignore-next-line  */
            $this->expectException(UserNotFoundException::class);
        } else {
            /** @phpstan-ignore-next-line  */
            $this->expectException(UsernameNotFoundException::class);
        }
    }
}

/**
 * @implements ObjectRepository<User>
 */
abstract class UserLoaderRepository implements ObjectRepository, UserLoaderInterface
{
    abstract public function loadUserByIdentifier(string $identifier): ?UserInterface;
}

/**
 * @implements ObjectRepository<User>
 */
abstract class UsernameLoaderRepository implements ObjectRepository, UserLoaderInterface
{
    abstract public function loadUserByUsername($username): ?UserInterface;

    abstract public function loadUserByIdentifier(string $identifier): ?UserInterface;
}

/**
 * @implements ObjectRepository<User>
 */
abstract class EntityRepository implements ObjectRepository
{
    abstract public function loadUserByIdentifier(string $identifier): ?UserInterface;
}

/**
 * @implements ObjectRepository<User>
 */
abstract class UserProviderRepository implements ObjectRepository, UserProviderInterface
{
    abstract public function refreshUser(UserInterface $user): UserInterface;
}

abstract class AbstractUser implements UserInterface {}
