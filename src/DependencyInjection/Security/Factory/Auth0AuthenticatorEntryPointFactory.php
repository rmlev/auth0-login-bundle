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
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\EntryPointFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Implementation EntryPointFactoryInterface for Symfony 5.1
 */
final class Auth0AuthenticatorEntryPointFactory extends Auth0AbstractFactory implements AuthenticatorFactoryInterface, EntryPointFactoryInterface
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

    public function addConfiguration(NodeDefinition $builder)
    {
        parent::addConfiguration($builder);
        $this->nodeConfigurator->addConfiguration($builder);
    }

    public function registerEntryPoint(ContainerBuilder $container, string $id, array $config): string
    {
        $entryPointId = 'rmlev_auth0_login.authentication.entry_point.'.$id;
        $authenticatorId =  self::BASE_AUTHENTICATOR_SERVICE_ID.'.'.$id;

        $container->setAlias($entryPointId, $authenticatorId);

        return $entryPointId;
    }
}
