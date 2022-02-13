<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('rmlev_auth0_login');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('domain')->isRequired()->cannotBeEmpty()
                    ->info('The URL of your Auth0 tenant domain')
                ->end()
                ->scalarNode('client_id')->isRequired()->cannotBeEmpty()
                    ->info('Your Auth0 application\'s Client ID')
                ->end()
                ->scalarNode('client_secret')->isRequired()->cannotBeEmpty()
                    ->info('Your Auth0 application\'s Client Secret')
                ->end()
                ->scalarNode('cookie_secret')->isRequired()->cannotBeEmpty()
                    ->info('A long, secret value used to encrypt the session cookie')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
