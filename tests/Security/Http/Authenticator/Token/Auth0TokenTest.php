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

namespace Rmlev\Auth0LoginBundle\Tests\Security\Http\Authenticator\Token;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Token\Auth0Token;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\UserPassportInterface;

final class Auth0TokenTest extends TestCase
{
    private string $accessToken = 'test.access_token';

    private string $idToken = 'test.id_token';

    private string $refreshToken = 'test.refresh_token';

    private \DateTimeImmutable $expiresAt;

    private ?Auth0Token $token = null;

    protected function setUp(): void
    {
        if (interface_exists(Passport::class)) {
            $passwordStub = $this->createStub(Passport::class);

            $this->expiresAt = new \DateTimeImmutable();
            $map = [
                ['access_token', '', $this->accessToken],
                ['id_token', '', $this->idToken],
                ['refresh_token', '', $this->refreshToken],
                ['expires_at', null, $this->expiresAt]
            ];
            $passwordStub->method('getAttribute')
                ->willReturnMap($map);

            $userStub = $this->createStub(UserInterface::class);
            $userStub->method('getRoles')
                ->willReturn(['ROLE_USER', 'ROLE_AUTH0_USER']);

            $passwordStub->method('getUser')
                ->willReturn($userStub);

            $this->token = new Auth0Token($passwordStub, 'main');
        } else {
            $this->markTestSkipped('Interface ' . Passport::class . ' does not exist');
        }
    }

    public function testGetAccessToken(): void
    {
        $this->assertSame($this->accessToken, $this->token->getAccessToken());
    }

    public function testGetRefreshToken(): void
    {
        $this->assertSame($this->refreshToken, $this->token->getRefreshToken());
    }

    public function testGetIdToken(): void
    {
        $this->assertSame($this->idToken, $this->token->getIdToken());
    }

    public function testGetExpiresAt(): void
    {
        $this->assertSame($this->expiresAt, $this->token->getExpiresAt());
    }

    public function testSerialization(): void
    {
        $auth0TokenClone = clone $this->token;

        $serialized = $this->token->__serialize();

        $this->expiresAt = new \DateTimeImmutable();
        $map = [
            ['access_token', '', 'fake.access.token'],
            ['id_token', '', 'fake.id.token'],
            ['refresh_token', '', 'fake.refresh.token'],
            ['expires_at', null, $this->expiresAt]
        ];
        $passwordStub = $this->createStub(Passport::class);
        $passwordStub->method('getAttribute')
            ->willReturnMap($map);
        $userStub = $this->createStub(UserInterface::class);
        $userStub->method('getRoles')
            ->willReturn(['ROLE_USER', 'ROLE_AUTH0_USER']);

        $passwordStub->method('getUser')
            ->willReturn($userStub);
        $token = new Auth0Token($passwordStub, 'fake');
        $token->__unserialize($serialized);

        $this->assertEquals($auth0TokenClone, $token);

        $serialized[0] = 'fake';
        $fakeToken = new Auth0Token($passwordStub, 'fake');
        $fakeToken->__unserialize($serialized);
        $this->assertNotEquals($auth0TokenClone, $fakeToken);
    }

    /**
     * @dataProvider tokenAttributesDataProvider
     * @param array<string> $attributes
     */
    public function testAuth0TokenLegacy(array $attributes): void
    {
        $userMock = $this->createMock(UserInterface::class);
        $userMock
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_USER', 'ROLE_AUTH0_USER']);
        $passportMock = $this->createMock(UserPassportInterface::class);
        $passportMock
            ->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($userMock);

        $token = new Auth0Token($passportMock, 'firewall', new ParameterBag($attributes));

        $this->assertSame($attributes['access_token'], $token->getAccessToken());
        $this->assertSame($attributes['id_token'], $token->getIdToken());
        $this->assertSame($attributes['refresh_token'], $token->getRefreshToken());
        $this->assertSame($attributes['expires_at'], $token->getExpiresAt());
    }

    public function tokenAttributesDataProvider(): \Generator
    {
        yield [
            [
                'access_token' => 'access_token.stub',
                'id_token' => 'id_token.stub',
                'refresh_token' => 'refresh_token.stub',
                'expires_at' => new \DateTimeImmutable('+ 1000 seconds'),
            ]
        ];

        yield [
            [
                'access_token' => 'access_token.stub2',
                'id_token' => 'id_token.stub2',
                'refresh_token' => 'refresh_token.stub2',
                'expires_at' => new \DateTimeImmutable('+ 100 seconds'),
            ]
        ];
    }
}
