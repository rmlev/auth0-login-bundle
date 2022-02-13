<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Helper;

use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\HttpUtils;

final class Auth0Helper
{
    private FirewallMap $firewallMap;
    private HttpUtils $httpUtils;
    private RequestStack $requestStack;
    private Auth0OptionsCollection $options;

    public function __construct(FirewallMap $firewallMap, HttpUtils $httpUtils, RequestStack $requestStack, Auth0OptionsCollection $options)
    {
        $this->firewallMap = $firewallMap;
        $this->httpUtils = $httpUtils;
        $this->requestStack = $requestStack;
        $this->options = $options;
    }

    public function supports(Request $request): bool
    {
        $haveCode = (bool) $request->query->get('code');

        $firewallName = $this->firewallMap->getFirewallConfig($request)->getName();
        if ($this->httpUtils->checkRequestPath($request, $this->getOptions($firewallName)['check_path']) && $haveCode) {
            return true;
        }
        return false;
    }

    public function createUserNotFoundException(string $message = '', string $userIdentifier = ''): AuthenticationException
    {
        if (class_exists(UserNotFoundException::class)) {
            $e = new UserNotFoundException($message);
            if ($userIdentifier) {
                $e->setUserIdentifier($userIdentifier);
            }
            /** @phpstan-ignore-next-line  */
            return $e;
        }

        /** @phpstan-ignore-next-line  */
        return new UsernameNotFoundException($message);
    }

    public function getOptions(string $firewallName = null): ?array
    {
        return $this->options->getOptions($firewallName);
    }

    public function getLogoutReturnURI(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $firewallName = $this->firewallMap->getFirewallConfig($request)->getName();
        $logoutRedirectPath = $this->getOptions($firewallName)['logout_redirect_path'];

        return $this->httpUtils->generateUri($request, $logoutRedirectPath);
    }

    public function getRedirectURI(string $firewallName = null): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $callbackPath = $this->getOptions($firewallName)['check_path'];
        return $this->httpUtils->generateUri($request, $callbackPath);
    }
}
