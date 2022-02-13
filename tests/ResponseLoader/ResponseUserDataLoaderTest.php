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

namespace Rmlev\Auth0LoginBundle\Tests\ResponseLoader;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\ResponseLoader\ResponseUserDataLoader;
use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0User;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ResponseUserDataLoaderTest extends TestCase
{
    private ResponseUserDataLoader $mapper;

    protected function setUp(): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->mapper = new ResponseUserDataLoader($propertyAccessor);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testMapOptions(array $options): void
    {
        $this->mapper->setMapOptions($options);

        $this->assertSame($options, $this->mapper->getMapOptions());
    }

    public function optionsDataProvider(): \Generator
    {
        yield [
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ]
        ];

        yield [
            [
                'k1' => 'val-1',
                'k2' => 'val-2',
                'k3' => 'val-3',
            ]
        ];
    }

    /**
     * @dataProvider identifierDataProvider
     */
    public function testIdentifier(string $identifier, array $options, string $identifierAuth0Side): void
    {
        $this->mapper->setIdentifierUserSide($identifier);
        $this->mapper->setMapOptions($options);

        $this->assertSame($identifierAuth0Side, $this->mapper->getIdentifierAuth0Side());
    }

    public function identifierDataProvider(): \Generator
    {
        yield [
            'test.id',
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'test.oauth' => 'test.id',
                'key3' => 'value3',
            ],
            'test.oauth'
        ];

        yield [
            'value2',
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'test.oauth' => 'test.id',
                'key3' => 'value3',
            ],
            'key2'
        ];
    }

    /**
     * @dataProvider addMapOptionsDataProvider
     */
    public function testAddMapOptions(array $options1, array $options2, array $resultOptions): void
    {
        $this->mapper->setMapOptions($options1);
        $this->mapper->addMapOptions($options2);

        $this->assertSame($resultOptions, $this->mapper->getMapOptions());
    }

    public function addMapOptionsDataProvider(): \Generator
    {
        yield [
            [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            [
                'key3' => 'value3',
            ],
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
        ];

        yield [
            [
                'key1' => 'value1',
                'key3' => 'value3',
            ],
            [
                'key2' => 'value2',
            ],
            [
                'key1' => 'value1',
                'key3' => 'value3',
                'key2' => 'value2',
            ],
        ];

        yield [
            [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            [
                'key2' => 'value3',
            ],
            [
                'key1' => 'value1',
                'key2' => 'value3',
            ],
        ];
    }

    /**
     * @dataProvider userPropertiesDataProvider
     */
    public function testLoadUserProperties(string $identifier, array $responseUserData): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $responseMapper = new ResponseUserDataLoader($propertyAccessor);

        $user = new Auth0User($identifier);

        $responseMapper->loadUserProperties($user, new ParameterBag($responseUserData));

        $this->assertSame($responseUserData['nickname'], $user->getNickname());
        $this->assertSame($responseUserData['name'], $user->getUsername());
        $this->assertSame($responseUserData['email'], $user->getEmail());
        $this->assertSame($responseUserData['sub'], $user->getAuth0UserKey());
        $this->assertSame($responseUserData['picture'], $user->getAvatar());
    }

    public function userPropertiesDataProvider(): \Generator
    {
        yield [
            'test@test.mail.com',
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
        ];

        yield [
            'stub@example.com',
            [
                'nickname' => 'stub',
                'name' => 'stub@example.com',
                'picture' => 'https://test.gravatar.com/avatar/789.png',
                'email' => 'stub@example.com',
                'email_verified' => true,
                'sub' => 'stub.1234',
            ],
        ];
    }

    /**
     * @dataProvider checkUserPropertiesDataProvider
     */
    public function testCheckUserProperties(array $userProperties, array $responseUserData, bool $ok): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $responseMapper = new ResponseUserDataLoader($propertyAccessor);

        $user = new Auth0User($userProperties['email']);
        $user->setNickname($userProperties['nickname']);
        $user->setEmail($userProperties['email']);
        $user->setAuth0UserKey($userProperties['auth0UserKey']);
        $user->setAvatar('https://test.gravatar.com/avatar/12345.png');

        if ($ok) {
            $this->assertTrue(
                $responseMapper->checkUserProperties($user, new ParameterBag($responseUserData))
            );
        } else {
            $this->assertFalse(
                $responseMapper->checkUserProperties($user, new ParameterBag($responseUserData))
            );
        }
    }

    public function checkUserPropertiesDataProvider(): \Generator
    {
        yield [
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'auth0UserKey' => 'abcd.1234',
                'avatar' => 'https://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            true
        ];

        yield [
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'auth0UserKey' => 'abc.1234',
                'avatar' => 'https://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            false
        ];
    }

    /**
     * @dataProvider keyAuth0SideDataProvider
     */
    public function testKeyAuth0Side(array $userProperties, array $responseUserData, string $keyAuth0Side, bool $ok): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $responseMapper = new ResponseUserDataLoader($propertyAccessor);
        $responseMapper->setKeyAuth0Side($keyAuth0Side);

        $user = new Auth0User($userProperties['email']);
        $user->setName($userProperties['name']);
        $user->setNickname($userProperties['nickname']);
        $user->setEmail($userProperties['email']);
        $user->setAuth0UserKey($userProperties['auth0UserKey']);
        $user->setAvatar($userProperties['avatar']);

        if ($ok) {
            $this->assertTrue(
                $responseMapper->checkUserProperties($user, new ParameterBag($responseUserData))
            );
        } else {
            $this->assertFalse(
                $responseMapper->checkUserProperties($user, new ParameterBag($responseUserData))
            );
        }
    }

    public function keyAuth0SideDataProvider(): \Generator
    {
        yield [
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'auth0UserKey' => 'abcd.1234',
                'avatar' => 'https://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            'sub',
            true
        ];

        yield [
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'auth0UserKey' => 'test',
                'avatar' => 'https://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            'nickname',
            true
        ];

        yield [
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'auth0UserKey' => 'test@test.mail.com',
                'avatar' => 'https://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            'email',
            true
        ];

        yield [
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'auth0UserKey' => 'test@test.mail.com',
                'avatar' => 'https://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            'name',
            true
        ];

        yield [
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'auth0UserKey' => 'test@test.mail.com',
                'avatar' => 'https://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            'picture',
            true
        ];

        yield [
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'auth0UserKey' => 'test@test.mail.com',
                'avatar' => 'http://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            'picture',
            false
        ];

        yield [
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'name' => 'test',
                'auth0UserKey' => 'test@test.mail.com',
                'avatar' => 'https://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            'name',
            false
        ];

        yield [
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'auth0UserKey' => 'abcd.1234',
                'avatar' => 'https://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'stub@example.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            'sub',
            false
        ];
    }

    /**
     * @dataProvider allParametersDataProvider
     */
    public function testCheckAllParameters(string $identifier, string $auth0Key, array $mapOptions, array $userProperties, array $responseUserData, bool $ok): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $responseMapper = new ResponseUserDataLoader($propertyAccessor, $identifier, $auth0Key, $mapOptions);

        $user = new Auth0User($userProperties['email']);
        $user->setName($userProperties['name']);
        $user->setNickname($userProperties['nickname']);
        $user->setEmail($userProperties['email']);
        $user->setAuth0UserKey($userProperties['auth0UserKey']);
        $user->setAvatar($userProperties['avatar']);

        if ($ok) {
            $this->assertTrue(
                $responseMapper->checkUserProperties($user, new ParameterBag($responseUserData))
            );
        } else {
            $this->assertFalse(
                $responseMapper->checkUserProperties($user, new ParameterBag($responseUserData))
            );
        }
    }

    public function allParametersDataProvider(): \Generator
    {
        yield [
            'email',
            'sub',
            [
                'nickname' => 'nickname',
                'name' => 'name',
                'email' => 'email',
                'sub' => 'auth0_user_key',
                'picture' => 'avatar'
            ],
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'auth0UserKey' => 'abcd.1234',
                'avatar' => 'https://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            true,
        ];

        yield [
            'nickname',
            'sub',
            [
                'nickname' => 'nickname',
                'name' => 'name',
                'email' => 'email',
                'sub' => 'auth0_user_key',
                'picture' => 'avatar'
            ],
            [
                'email' => 'test@test.mail.com',
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'auth0UserKey' => 'abcd.1234',
                'avatar' => 'https://test.gravatar.com/avatar/12345.png'
            ],
            [
                'nickname' => 'test',
                'name' => 'test@test.mail.com',
                'picture' => 'https://test.gravatar.com/avatar/12345.png',
                'email' => 'test@test.mail.com',
                'email_verified' => true,
                'sub' => 'abcd.1234',
            ],
            false,
        ];
    }

    public function testCheckUserIdentifier(): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $responseMapper = new ResponseUserDataLoader($propertyAccessor);

        $responseUserData = [
            'nickname' => 'test',
            'name' => 'test@test.mail.com',
            'picture' => 'https://test.gravatar.com/avatar/12345.png',
            'email' => 'test@test.mail.com',
            'email_verified' => true,
            'sub' => 'abcd.1234',
        ];

        $this->assertTrue(
            $responseMapper->checkUserIdentifier('test@test.mail.com', new ParameterBag($responseUserData))
        );

        $this->assertFalse(
            $responseMapper->checkUserIdentifier('test1@test.mail.com', new ParameterBag($responseUserData))
        );
    }
}
