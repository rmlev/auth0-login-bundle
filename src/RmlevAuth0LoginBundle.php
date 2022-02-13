<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle;

use Rmlev\Auth0LoginBundle\DependencyInjection\Compiler\OptionsCompilerPass;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0AuthenticatorEntryPointFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0AuthenticatorFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\Factory\Auth0SecurityFactory;
use Rmlev\Auth0LoginBundle\DependencyInjection\Security\UserProvider\Auth0MemoryFactory;
use Symfony\Bridge\Doctrine\DependencyInjection\Security\UserProvider\EntityFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\EntryPointFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class RmlevAuth0LoginBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var SecurityExtension $securityExtension */
        $securityExtension = $container->getExtension('security');

        if (method_exists($securityExtension, 'addAuthenticatorFactory')) {
            $securityExtension->addAuthenticatorFactory(
                new Auth0AuthenticatorFactory()
            );
        } elseif (interface_exists(AuthenticatorFactoryInterface::class)) {
            if (!interface_exists(EntryPointFactoryInterface::class)) {
                $securityExtension->addSecurityListenerFactory(
                    new Auth0AuthenticatorFactory()
                );
            } else {
                // for Symfony 5.1
                $securityExtension->addSecurityListenerFactory(
                    new Auth0AuthenticatorEntryPointFactory()
                );
            }
        } else {
            $securityExtension->addSecurityListenerFactory(new Auth0SecurityFactory());
        }

        $securityExtension->addUserProviderFactory(new Auth0MemoryFactory());
        if ($container->hasExtension('doctrine')) {
            if (class_exists(EntityFactory::class)) {
                $securityExtension->addUserProviderFactory(new EntityFactory('auth0-entity', 'rmlev_auth0_login.security_core_user.entity_user_provider'));
            }
        }

        $container->addCompilerPass(new OptionsCompilerPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
