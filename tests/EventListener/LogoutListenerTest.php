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

namespace Rmlev\Auth0LoginBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\EventListener\LogoutListener;
use Rmlev\Auth0LoginBundle\Helper\Auth0LogoutHandler;
use Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Token\Auth0Token;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class LogoutListenerTest extends TestCase
{
    public function testOnLogoutEvent(): void
    {
        if (!class_exists(LogoutEvent::class)) {
            $this->markTestSkipped('Class ' . LogoutEvent::class . 'does not exist');
        }

        $redirectUrl = '/stub_url';

        $auth0LogoutHandlerMock = $this->createMock(Auth0LogoutHandler::class);
        $auth0LogoutHandlerMock->expects($this->once())
            ->method('onLogout')
            ->willReturn(new RedirectResponse($redirectUrl));

        $listener = new LogoutListener($auth0LogoutHandlerMock);

        $request = Request::create('https://example.com/stub');
        $tokenStub = $this->createStub(Auth0Token::class);
        $event = new LogoutEvent($request, $tokenStub);

        $listener->onLogoutEvent($event);

        $response = $event->getResponse();

        $this->assertSame(302, $response->getStatusCode());

        $url = $response->headers->get('location');
        $this->assertSame($redirectUrl, $url);
    }

    public function testLogoutEventDoesNotExist()
    {
        if (class_exists(LogoutEvent::class)) {
            $this->markTestSkipped('Class ' . LogoutEvent::class . ' exist');
        }

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You can\'t use LogoutListener prior to Symfony 5.1. Use logout success handler instead');

        $auth0LogoutHandlerStub = $this->createStub(Auth0LogoutHandler::class);

        new LogoutListener($auth0LogoutHandlerStub);
    }
}
