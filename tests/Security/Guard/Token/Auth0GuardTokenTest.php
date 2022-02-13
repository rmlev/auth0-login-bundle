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

namespace Rmlev\Auth0LoginBundle\Tests\Security\Guard\Token;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0User;
use Rmlev\Auth0LoginBundle\Security\Guard\Token\Auth0GuardToken;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

final class Auth0GuardTokenTest extends TestCase
{
    private Auth0GuardToken $token;
    private ParameterBag $attributes;

    protected function setUp(): void
    {
        if (class_exists(AbstractGuardAuthenticator::class) === false) {
            $this->markTestSkipped(sprintf('Class "%s" not found', AbstractGuardAuthenticator::class));
        }

        $user = new Auth0User('test@example.com');

        $this->attributes = new ParameterBag([
            'access_token' => 'test.access_token',
            'id_token' => 'test.id_token',
            'refresh_token' => 'test.refresh_token',
            'expires_at' => new \DateTimeImmutable(),
        ]);

        $this->token = new Auth0GuardToken($user, 'fw', $this->attributes);
    }

    public function testGetAccessToken(): void
    {
        $this->assertSame($this->attributes->get('access_token'), $this->token->getAccessToken());
    }

    public function testGetIdToken(): void
    {
        $this->assertSame($this->attributes->get('id_token'), $this->token->getIdToken());
    }

    public function testGetRefreshToken(): void
    {
        $this->assertSame($this->attributes->get('refresh_token'), $this->token->getRefreshToken());
    }

    public function testGetExpiresAt(): void
    {
        $this->assertSame($this->attributes->get('expires_at'), $this->token->getExpiresAt());
    }

    public function testSerialization(): void
    {
        $this->assertEquals(
            $this->token,
            unserialize(serialize($this->token))
        );
    }
}
