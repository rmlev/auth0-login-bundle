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

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

final class Auth0AuthenticatorFactory extends Auth0AbstractFactory implements AuthenticatorFactoryInterface
{
    private SecurityNodeConfiguratorInterface $nodeConfigurator;

    public function __construct(
        SecurityNodeConfiguratorInterface $nodeConfigurator = null,
        Auth0UserProviderFactoryInterface $userProviderFactory = null
    )
    {
        parent::__construct($userProviderFactory ?? new Auth0UserProviderFactory());
        $this->nodeConfigurator = $nodeConfigurator ?? new SecurityNodeConfigurator();
    }

    public function getPriority(): int
    {
        return $this->nodeConfigurator->getPriority();
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): string
    {
        return $this->nodeConfigurator->getPosition();
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return $this->nodeConfigurator->getKey();
    }

    public function addConfiguration(NodeDefinition $builder): void
    {
        parent::addConfiguration($builder);
        $this->nodeConfigurator->addConfiguration($builder);
    }
}
