<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class OptionsCompilerPass implements CompilerPassInterface
{
    const OPTIONS_SERVICE_ID = 'rmlev_auth0_login.helper.auth0options';

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('rmlev_auth0_login_options_collection') as $collectionId => $collectionTags) {
            $collectionDefinition = $container->getDefinition($collectionId);
            foreach ($container->findTaggedServiceIds('rmlev_auth0_login_options') as $id => $tags) {
                $optionsDefinitionReference = new Reference($id);
                foreach ($tags as $attributes) {
                    if (array_key_exists('firewall', $attributes)) {
                        $collectionDefinition->addMethodCall(
                            'addOptions',
                            [$optionsDefinitionReference, $attributes['firewall']]
                        );
                    }
                }
            }
        }
    }
}
