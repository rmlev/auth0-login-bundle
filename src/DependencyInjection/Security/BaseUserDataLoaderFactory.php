<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\DependencyInjection\Security;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

abstract class BaseUserDataLoaderFactory
{
    const BASE_USER_DATA_LOADER_ID = 'rmlev_auth0_login.helper_auth0response.response_user_data_loader';

    protected function createUserDataLoaderReference(ContainerBuilder $container, array $config, string $userDataLoaderServiceId): Reference
    {
        if (!($this->hasLoaderConfiguration($config))) {
            return new Reference(self::BASE_USER_DATA_LOADER_ID);
        }

        switch (key($config['user_data_loader'])) {
            case 'service':
                return new Reference($config['user_data_loader']['service']);
            case 'default':
                $identifier = (array_key_exists('identifier', $config['user_data_loader']['default'])) ?
                    $config['user_data_loader']['default']['identifier'] : null;
                $auth0Key = (array_key_exists('auth0_key', $config['user_data_loader']['default'])) ?
                    $config['user_data_loader']['default']['auth0_key'] : null;
                $mapOptions = (array_key_exists('map_options', $config['user_data_loader']['default'])) ?
                    $config['user_data_loader']['default']['map_options'] : [];

                $container
                    ->setDefinition($userDataLoaderServiceId, new ChildDefinition(self::BASE_USER_DATA_LOADER_ID))
                    ->setArgument('$identifier', $identifier)
                    ->setArgument('$auth0Key', $auth0Key)
                    ->setArgument('$mapOptions', $mapOptions);
                break;
        }

        return new Reference($userDataLoaderServiceId);
    }

    /**
     * @param array $config
     * @return bool
     */
    protected function hasLoaderConfiguration(array $config): bool
    {
        if (!\array_key_exists('user_data_loader', $config)) {
            return false;
        }
        if ($config['user_data_loader'] === null) {
            return false;
        }
        if ($config['user_data_loader'] === []) {
            return false;
        }

        return true;
    }
}
