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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class RmlevAuth0LoginExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $factoryDefinition = $container->getDefinition('rmlev_auth0_login.connector_auth0_factory.auth0factory');
        $factoryDefinition->setArgument(0, $config['domain']);
        $factoryDefinition->setArgument(1, $config['client_id']);
        $factoryDefinition->setArgument(2, $config['client_secret']);
        $factoryDefinition->setArgument(3, $config['cookie_secret']);

        $authenticatorDefinition = $container->getDefinition('rmlev_auth0_login.security.authenticator');
    }
}
