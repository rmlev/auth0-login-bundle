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
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\Helper\Auth0Options;
use Rmlev\Auth0LoginBundle\Helper\Auth0OptionsCollection;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\HttpUtils;

final class Auth0HelperTest extends TestCase
{
    private Auth0Helper $auth0Helper;

    private array $testOptions = [
        'check_path' => '/callback_test',
        'use_forward' => false,
        'require_previous_session' => false,
        'login_path' => '/login',
        'logout_redirect_path' => '/logout/redirect/path',
    ];

    private string $baseUrl = 'https://example.com';
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $firewallConfigMock = $this->createMock(FirewallConfig::class);
        $firewallConfigMock->method('getName')
            ->willReturn('stub');

        $firewallMapMock = $this->createMock(FirewallMap::class);
        $firewallMapMock->method('getFirewallConfig')
            ->willReturn($firewallConfigMock);

        $httpUtilsStub = $this->createStub(HttpUtils::class);
        $httpUtilsStub
            ->method('checkRequestPath')
            ->will(
                $this->returnCallback(function () {
                    $request = func_get_args()[0];
                    return $request->getPathInfo() === $this->testOptions['check_path'];
                })
            );

        $this->requestStack = new RequestStack();
        $request = Request::create($this->baseUrl);
        $this->requestStack->push($request);

        $auth0Options = new Auth0Options($this->testOptions);
        $auth0OptionsCollection = new Auth0OptionsCollection();
        $auth0OptionsCollection->addOptions($auth0Options, 'stub');

        $this->auth0Helper = new Auth0Helper($firewallMapMock, $httpUtilsStub, $this->requestStack, $auth0OptionsCollection);
    }

    public function testSupports(): void
    {
        $request = Request::create($this->baseUrl.'/callback_test');
        $request->query->set('code', '1234ABC');

        $this->assertTrue($this->auth0Helper->supports($request));

        $request = Request::create($this->baseUrl.'/callback_test');
        $this->assertFalse($this->auth0Helper->supports($request));

        $request = Request::create($this->baseUrl.'/callback_wrong');
        $request->query->set('code', '1234ABC');
        $this->assertFalse($this->auth0Helper->supports($request));
    }

    public function testCreateUserNotFoundException(): void
    {
        /**
         * @var UserNotFoundException|UsernameNotFoundException $e
         * @phpstan-ignore-next-line
         */
        $e = $this->auth0Helper->createUserNotFoundException('Test Exception', '123');
        if (class_exists(UserNotFoundException::class)) {
            $this->assertInstanceOf(UserNotFoundException::class, $e);
            /** @phpstan-ignore-next-line  */
            $this->assertSame('123', $e->getUserIdentifier());
            /** @phpstan-ignore-next-line  */
            $this->expectException(UserNotFoundException::class);
        } else {
            /** @phpstan-ignore-next-line  */
            $this->assertInstanceOf(UsernameNotFoundException::class, $e);
            /** @phpstan-ignore-next-line  */
            $this->expectException(UsernameNotFoundException::class);
        }
        /** @phpstan-ignore-next-line  */
        throw $e;
    }

    public function testGetOptions(): void
    {
        $this->assertSame($this->testOptions, $this->auth0Helper->getOptions());
    }

    public function testGetLogoutReturnURI(): void
    {
        $auth0Helper = $this->getAuth0Helper();

        $this->assertSame( $this->baseUrl. $this->testOptions['logout_redirect_path'], $auth0Helper->getLogoutReturnURI());
    }

    public function testGetRedirectURI(): void
    {
        $auth0Helper = $this->getAuth0Helper();

        $this->assertSame( $this->baseUrl. $this->testOptions['check_path'], $auth0Helper->getRedirectURI());
    }

    /**
     * @return Auth0Helper
     */
    private function getAuth0Helper(): Auth0Helper
    {
        $firewallName = 'stub';

        $firewallConfigMock = $this->createMock(FirewallConfig::class);
        $firewallConfigMock->method('getName')
            ->willReturn($firewallName);
        $firewallMapMock = $this->createMock(FirewallMap::class);
        $firewallMapMock->method('getFirewallConfig')
            ->willReturn($firewallConfigMock);

        $httpUtils = new HttpUtils();
        $auth0Options = new Auth0Options($this->testOptions);
        $auth0OptionsCollection = new Auth0OptionsCollection();
        $auth0OptionsCollection->addOptions($auth0Options, $firewallName);
        return new Auth0Helper($firewallMapMock, $httpUtils, $this->requestStack, $auth0OptionsCollection);
    }
}
