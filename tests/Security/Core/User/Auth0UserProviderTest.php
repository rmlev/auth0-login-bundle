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

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\Auth0Credentials;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\Helper\Auth0OptionsCollection;
use Rmlev\Auth0LoginBundle\ResponseLoader\ResponseUserDataLoader;
use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0User;
use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0UserProvider;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\HttpUtils;

final class Auth0UserProviderTest extends TestCase
{
    private string $testEmail = 'test.user@example.com';

    private Auth0UserProvider $userProvider;

    protected function setUp(): void
    {
        $credentials = new Auth0Credentials(
            [
                'email' => $this->testEmail,
                'nickname' => 'test.nickname',
                'sub' => 'test.sub',
            ],
            'idToken.test',
            'accessToken.test',
            ['openid', 'profile', 'email'],
            time()+100,
            false
        );

        $connectorStub = $this->createStub(ConnectorInterface::class);
        $connectorStub
            ->method('getCredentials')
            ->willReturn($credentials);

        $firewallMapStab = $this->createStub(FirewallMap::class);
        $auth0Helper = new Auth0Helper($firewallMapStab, new HttpUtils(), new RequestStack(), new Auth0OptionsCollection());

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $mapper = new ResponseUserDataLoader($propertyAccessor);

        $this->userProvider = new Auth0UserProvider($connectorStub, $auth0Helper, $mapper);
    }

    public function testLoadUserNotFound(): void
    {
        $this->expectUserNotFoundException();
        $this->expectExceptionMessage("User 'fakeid' not found.");
        $this->userProvider->loadUserByIdentifier('fakeid');
    }

    public function testLoadUserByIdentifier(): void
    {
        $user = $this->userProvider->loadUserByIdentifier($this->testEmail);
        /** @phpstan-ignore-next-line  */
        $this->assertSame($this->testEmail, $user->getUserIdentifier());
    }

    /**
     * @dataProvider loadUserIdentifierDataProvider
     */
    public function testLoadUserByUsername(string $username, bool $ok): void
    {
        if (!$ok) {
            $this->expectUserNotFoundException();
        }

        $user = $this->userProvider->loadUserByUsername($username);
        /** @phpstan-ignore-next-line  */
        $this->assertSame($username, $user->getUsername());
    }

    public function loadUserIdentifierDataProvider(): \Generator
    {
        yield [
            $this->testEmail,
            true,
        ];

        yield [
            'stub',
            false,
        ];
    }

    public function testCredentialsNotFound(): void
    {
        $this->expectUserNotFoundException();

        $connectorMock = $this->createMock(ConnectorInterface::class);
        $connectorMock
            ->expects($this->once())
            ->method('getCredentials')
            ->willReturn(null);

        $firewallMapStab = $this->createStub(FirewallMap::class);
        $auth0Helper = new Auth0Helper($firewallMapStab, new HttpUtils(), new RequestStack(), new Auth0OptionsCollection());

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $mapper = new ResponseUserDataLoader($propertyAccessor);

        $userProvider = new Auth0UserProvider($connectorMock, $auth0Helper, $mapper);

        $userProvider->loadUserByIdentifier('stub-id');
    }

    /**
     * @dataProvider responseUserDataProvider
     */
    public function testLoadUserFromAuth0Response(array $responseData): void
    {
        $userData = new ParameterBag($responseData);

        /** @var Auth0User $user */
        $user = $this->userProvider->loadUserFromAuth0Response($userData);

        $this->assertSame($userData->get('nickname'), $user->getNickname());
        $this->assertSame($userData->get('name'), $user->getName());
        $this->assertSame($userData->get('email'), $user->getEmail());
        $this->assertSame($userData->get('sub'), $user->getAuth0UserKey());
        $this->assertSame($userData->get('picture'), $user->getAvatar());
    }

    public function responseUserDataProvider(): \Generator
    {
        yield [
            [
                'nickname' => 'test.nickname',
                'name' => 'test.name',
                'email' => 'test.email@test.mail.com',
                'sub' => 'test.id',
                'picture' => 'https://test.avatar/picture'
            ]
        ];

        yield [
            [
                'nickname' => 'stub.nickname',
                'name' => 'stub.name',
                'email' => 'stub@example.com',
                'sub' => 'stub.id',
                'picture' => 'https://stub.avatar/picture'
            ]
        ];
    }

    public function testSupportsClass(): void
    {
        $this->assertTrue($this->userProvider->supportsClass(Auth0User::class));
        $this->assertFalse($this->userProvider->supportsClass( UserInterface::class));
    }

    /**
     * @dataProvider getUserIdentifierDataProvider
     */
    public function testGetUserIdentifier(array $userData, bool $ok): void
    {
        if (!$ok) {
            $this->expectUserNotFoundException();
        }

        $userDataBag = new ParameterBag($userData);
        $identifier = $this->userProvider->getUserIdentifier($userDataBag);

        $this->assertSame($userDataBag->get('email'), $identifier);
    }

    public function getUserIdentifierDataProvider(): \Generator
    {
        yield [
            [
                'nickname' => 'stub.nickname',
                'name' => 'stub.name',
                'email' => 'stub@example.com',
                'sub' => 'stub.id',
                'picture' => 'https://stub.avatar/picture'
            ],
            true,
        ];

        yield [
            [
                'nickname' => 'stub.nickname',
                'name' => 'stub.name',
                'sub' => 'stub.id',
                'picture' => 'https://stub.avatar/picture'
            ],
            false,
        ];
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

    public function testRefreshUser(): void
    {
        $user = new Auth0User($this->testEmail);
        $refreshedUser = $this->userProvider->refreshUser($user);

        /** @phpstan-ignore-next-line  */
        $this->assertSame($user->getUserIdentifier(), $refreshedUser->getUserIdentifier());
    }

    public function testRefreshUserUnsupported(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $user = $this->createStub(UserInterface::class);

        $this->userProvider->refreshUser($user);
    }
}
