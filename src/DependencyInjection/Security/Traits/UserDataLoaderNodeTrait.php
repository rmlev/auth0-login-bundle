<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\DependencyInjection\Security\Traits;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

trait UserDataLoaderNodeTrait
{
    protected function addUserDataLoaderConfiguration(NodeDefinition $builder): void
    {
        /** @var ArrayNodeDefinition $builder */
        $builder
            ->treatNullLike(['user_data_loader' => []])
            ->children()
                ->arrayNode('user_data_loader')
                ->info('Settings for Auth0 User data loader service.')
                    ->children()
                        ->scalarNode('service')
                            ->info('Custom User data loader service. Custom implementation of ResponseUserDataLoaderInterface.')
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('default')
                                ->info('Default implementation of ResponseUserDataLoaderInterface.')
                            ->children()
                                ->scalarNode('identifier')
                                    ->info('Identifier for a User class in Symfony security system.')
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('auth0_key')
                                    ->info('User identifier on Auth0 server. Used to check User entity.')
                                    ->cannotBeEmpty()
                                ->end()
                                ->arrayNode('map_options')
                                    ->info('Map data between Auth0 response and User class')
                                    ->useAttributeAsKey('key')
                                    ->treatNullLike([])
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(function ($c) {
                            if ($c === []) {
                                return false;
                            }
                            return 1 !== \count($c) || !\in_array(key($c), ['service', 'default'], true);
                        })
                        ->thenInvalid("You should configure only one of: 'service', 'default'.")
                    ->end()
                ->end()
            ->end();
    }
}
