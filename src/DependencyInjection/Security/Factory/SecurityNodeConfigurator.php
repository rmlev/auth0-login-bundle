<?php

namespace Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory;

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Traits\UserDataLoaderNodeTrait;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

final class SecurityNodeConfigurator implements SecurityNodeConfiguratorInterface
{
    use UserDataLoaderNodeTrait;

    /**
     * @inheritDoc
     */
    public function getPosition(): string
    {
        return 'http';
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'auth0-login';
    }

    /**
     * Defines the position at which the authenticator is called
     */
    public function getPriority(): int
    {
        return 0;
    }

    public function addConfiguration(NodeDefinition $builder): void
    {
        /** @var ArrayNodeDefinition $builder */
        $builder
            ->children()
                ->scalarNode('check_path')->cannotBeEmpty()
                    ->defaultValue('auth0_callback')
                    ->info('Pathname for allowed Callback URL for your Auth0 application')
                ->end()
                ->scalarNode('logout_redirect_path')->cannotBeEmpty()
                    ->defaultValue('/')
                    ->info('Pathname to redirect to after logout from Auth0')
                ->end()
            ->end();

        $this->addUserDataLoaderConfiguration($builder);
        $builder->info('Auth0 Login authenticator configuration');
    }
}
