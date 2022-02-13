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

namespace Rmlev\Auth0LoginBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Controller\LoginController;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

final class LoginControllerTest extends TestCase
{
    /**
     * @dataProvider providerAuth0StartAction
     */
    public function testAuthStartAction(?string $firewallName): void
    {
        $connectorMock = $this->createMock(ConnectorInterface::class);
        $connectorMock->expects($this->once())
            ->method('login')
            ->willReturn('redirect_stub');

        $auth0HelperMock = $this->createMock(Auth0Helper::class);
        $auth0HelperMock
            ->expects($this->once())
            ->method('getRedirectURI')
            ->with($this->equalTo($firewallName))
            ->willReturn('stub');

        $controller = new LoginController(
            $connectorMock,
            $auth0HelperMock
        );

        $response = $controller->authStartAction($firewallName);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function providerAuth0StartAction(): \Generator
    {
        yield ['stub'];
        yield ['stub1'];
        yield ['stub2'];
        yield [null];
    }
}
