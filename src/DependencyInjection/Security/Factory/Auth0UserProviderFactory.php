<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory;

use Rmlev\Auth0LoginBundle\DependencyInjection\Security\BaseUserDataLoaderFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class Auth0UserProviderFactory extends BaseUserDataLoaderFactory implements Auth0UserProviderFactoryInterface
{
    const BASE_USER_PROVIDER_ID = 'rmlev_auth0_login.security.user.provider';

    public function createAuth0UserProvider(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId = null): Reference
    {
        $baseUserProviderServiceId = $userProviderId ?? self::BASE_USER_PROVIDER_ID;
        $userProviderServiceId = $baseUserProviderServiceId.'.'.$firewallName;

        $definition = $container
            ->setDefinition($userProviderServiceId, new ChildDefinition($baseUserProviderServiceId));

        if ($this->hasLoaderConfiguration($config)) {
            $definition
                ->replaceArgument('$responseUserDataLoader', $this->createUserDataLoader($container, $firewallName, $config));
        }

        return new Reference($userProviderServiceId);
    }

    private function createUserDataLoader(ContainerBuilder $container, string $firewallName, array $config): Reference
    {
        return $this->createUserDataLoaderReference(
            $container,
            $config,
            self::BASE_USER_DATA_LOADER_ID.'.'.$firewallName
        );
    }
}
