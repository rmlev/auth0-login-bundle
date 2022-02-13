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
use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0User;

final class Auth0UserTest extends TestCase
{
    public string $email = 'test@test.mail.com';

    public Auth0User $user;

    protected function setUp(): void
    {
        $this->user = new Auth0User($this->email);
    }

    public function testGetUserIdentifier(): void
    {
        $this->assertSame($this->email, $this->user->getUserIdentifier());
    }

    public function testGetUsername(): void
    {
        $this->assertSame($this->email, $this->user->getUsername());
    }

    public function testGetEmail(): void
    {
        $this->assertSame($this->email, $this->user->getEmail());
    }

    public function testSetEmail(): void
    {
        $email2 = 'test2@test.mail.com';
        $this->user->setEmail($email2);

        $this->assertSame($email2, $this->user->getEmail());
    }

    public function testSetNickname(): void
    {
        $nickname = 'testnickname';
        $this->user->setNickname($nickname);

        $this->assertSame($nickname, $this->user->getNickname());
    }

    public function testSetAuth0UserKey(): void
    {
        $auth0_id = '1234abcd';
        $this->user->setAuth0UserKey($auth0_id);

        $this->assertSame($auth0_id, $this->user->getAuth0UserKey());
    }

    public function testGetPassword(): void
    {
        $this->assertNull($this->user->getPassword());
    }

    public function testGetSalt(): void
    {
        $this->assertNull($this->user->getSalt());
    }

    public function testEraseCredentials(): void
    {
        $this->assertTrue($this->user->eraseCredentials());
    }
}
