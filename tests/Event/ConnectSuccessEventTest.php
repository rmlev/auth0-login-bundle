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

namespace Rmlev\Auth0LoginBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Event\ConnectSuccessEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class ConnectSuccessEventTest extends TestCase
{
    /**
     * @dataProvider connectSuccessDataProvider
     */
    public function testGetAuthenticatedToken(TokenInterface $token, Request $request, string $firewallName): void
    {
        $connectSuccessEvent = new ConnectSuccessEvent($token, $request, $firewallName);
        $this->assertSame($token, $connectSuccessEvent->getAuthenticatedToken());
    }

    /**
     * @dataProvider connectSuccessDataProvider
     */
    public function testGetRequest(TokenInterface $token, Request $request, string $firewallName): void
    {
        $connectSuccessEvent = new ConnectSuccessEvent($token, $request, $firewallName);
        $this->assertSame($request, $connectSuccessEvent->getRequest());
    }

    /**
     * @dataProvider connectSuccessDataProvider
     */
    public function testGetFirewallName(TokenInterface $token, Request $request, string $firewallName): void
    {
        $connectSuccessEvent = new ConnectSuccessEvent($token, $request, $firewallName);
        $this->assertSame($firewallName, $connectSuccessEvent->getFirewallName());
    }

    /**
     * @dataProvider connectSuccessDataProvider
     */
    public function testResponse(TokenInterface $token, Request $request, string $firewallName): void
    {
        $connectSuccessEvent = new ConnectSuccessEvent($token, $request, $firewallName);

        $response = new RedirectResponse('https://example.com/stub');
        $connectSuccessEvent->setResponse($response);
        $this->assertSame($response, $connectSuccessEvent->getResponse());
    }

    public function connectSuccessDataProvider(): \Generator
    {
        yield [
            $this->createStub(TokenInterface::class),
            Request::create('https://example.com'),
            'stub'
        ];

        yield [
            $this->createStub(TokenInterface::class),
            Request::create('https://example.com/stub/path'),
            'stub_firewall'
        ];
    }
}
