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

use Symfony\Component\Config\Definition\Builder\NodeDefinition;

interface SecurityNodeConfiguratorInterface
{
    /**
     * Defines the position at which the provider is called.
     * Possible values: pre_auth, form, http, and remember_me.
     *
     * @return string
     */
    public function getPosition(): string;

    /**
     * Defines the configuration key used to reference the provider
     * in the firewall configuration.
     *
     * @return string
     */
    public function getKey(): string;

    public function getPriority(): int;

    public function addConfiguration(NodeDefinition $builder): void;
}
