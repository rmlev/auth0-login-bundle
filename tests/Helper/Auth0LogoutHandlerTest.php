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

namespace Rmlev\Auth0LoginBundle\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\Helper\Auth0LogoutHandler;
use Rmlev\Auth0LoginBundle\Security\Guard\Token\Auth0GuardToken;
use Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Token\Auth0Token;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

final class Auth0LogoutHandlerTest extends TestCase
{
    public function testOnLogoutAuth0Token()
    {
        if (class_exists(PostAuthenticationToken::class) === false) {
            $this->markTestSkipped(sprintf('Class "%s" not found', PostAuthenticationToken::class));
        }

        $logoutUrl = '/logout_stub';
        $connectorMock = $this->createMock(ConnectorInterface::class);
        $connectorMock->expects($this->once())
            ->method('logout')
            ->willReturn($logoutUrl);

        $auth0HelperMock = $this->createMock(Auth0Helper::class);
        $auth0HelperMock->expects($this->once())
            ->method('getLogoutReturnURI');

        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $tokenStorageMock->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createStub(Auth0Token::class));

        $logoutHandler = new Auth0LogoutHandler($connectorMock, $auth0HelperMock, $tokenStorageMock);

        $response = $logoutHandler->onLogout();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($logoutUrl, $response->getTargetUrl());
    }

    public function testOnLogoutAuth0GuardToken()
    {
        if (class_exists(AbstractGuardAuthenticator::class) === false) {
            $this->markTestSkipped(sprintf('Class "%s" not found', AbstractGuardAuthenticator::class));
        }

        $logoutUrl = '/logout_stub';
        $connectorMock = $this->createMock(ConnectorInterface::class);
        $connectorMock->expects($this->once())
            ->method('logout')
            ->willReturn($logoutUrl);

        $auth0HelperMock = $this->createMock(Auth0Helper::class);
        $auth0HelperMock->expects($this->once())
            ->method('getLogoutReturnURI');

        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $tokenStorageMock->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createStub(Auth0GuardToken::class));

        $logoutHandler = new Auth0LogoutHandler($connectorMock, $auth0HelperMock, $tokenStorageMock);

        $response = $logoutHandler->onLogout();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($logoutUrl, $response->getTargetUrl());
    }

    public function testOnLogoutNotAuth0Token()
    {
        $logoutUrl = '/logout_stub';
        $connectorMock = $this->createMock(ConnectorInterface::class);
        $connectorMock->expects($this->never())
            ->method('logout')
            ->willReturn($logoutUrl);

        $auth0HelperMock = $this->createMock(Auth0Helper::class);
        $auth0HelperMock->expects($this->never())
            ->method('getLogoutReturnURI');

        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $tokenStorageMock->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createStub(TokenInterface::class));

        $logoutHandler = new Auth0LogoutHandler($connectorMock, $auth0HelperMock, $tokenStorageMock);

        $response = $logoutHandler->onLogout();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());
    }
}
