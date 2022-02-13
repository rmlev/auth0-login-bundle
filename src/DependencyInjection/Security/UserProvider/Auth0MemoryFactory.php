<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\DependencyInjection\Security\UserProvider;

use Rmlev\Auth0LoginBundle\DependencyInjection\Security\BaseUserDataLoaderFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Traits\UserDataLoaderNodeTrait;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\UserProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class Auth0MemoryFactory extends BaseUserDataLoaderFactory implements UserProviderFactoryInterface
{
    use UserDataLoaderNodeTrait;

    public function create(ContainerBuilder $container, $id, $config): void
    {
        $definition = $container
            ->setDefinition($id, new ChildDefinition('rmlev_auth0_login.security.user.provider'))
            ->replaceArgument('$responseUserDataLoader', $this->createUserDataLoader($container, $config));
        ;
    }

    public function getKey(): string
    {
        return 'auth0-memory';
    }

    public function addConfiguration(NodeDefinition $builder): void
    {
        $this->addUserDataLoaderConfiguration($builder);
    }

    private function createUserDataLoader(ContainerBuilder $container, array $config): Reference
    {
        return $this->createUserDataLoaderReference(
            $container,
            $config,
            'concrete.'.self::BASE_USER_DATA_LOADER_ID
        );
    }
}
