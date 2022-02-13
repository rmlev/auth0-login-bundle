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

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Rmlev\Auth0LoginBundle\Helper\Auth0Options;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

abstract class Auth0AbstractFactory extends AbstractFactory
{
    const BASE_AUTHENTICATOR_SERVICE_ID = 'rmlev_auth0_login.security.authenticator';
    const BASE_GUARD_AUTHENTICATOR_SERVICE_ID = 'rmlev_auth0_login.security.guard.authenticator';

    protected array $logoutOptions = [
        'logout_redirect_path' => '/',
    ];

    private Auth0UserProviderFactoryInterface $userProviderFactory;

    public function __construct(Auth0UserProviderFactoryInterface $userProviderFactory)
    {
        $this->options = array_merge($this->options, $this->logoutOptions);
        $this->userProviderFactory = $userProviderFactory;
    }

    abstract public function getPosition(): string;

    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId): array
    {
        $this->setOptions($container, $id, $config);
        $authenticatorId =  self::BASE_GUARD_AUTHENTICATOR_SERVICE_ID.'.'.$id;
        $userProvider = $this->userProviderFactory->createAuth0UserProvider($container, $id, $config, $userProviderId);

        $container
            ->setDefinition($authenticatorId, new ChildDefinition(self::BASE_GUARD_AUTHENTICATOR_SERVICE_ID))
            ->setArgument('$successHandler', new Reference($this->createAuthenticationSuccessHandler($container, $id, $config)))
            ->setArgument('$failureHandler', new Reference($this->createAuthenticationFailureHandler($container, $id, $config)))
            ->setArgument('$options', $this->getOptions($config))
            ->setArgument('$userProvider', $userProvider)
        ;

        $authenticatorReferences = [new Reference($authenticatorId)];

        $authenticators = new IteratorArgument($authenticatorReferences);

        // configure the GuardAuthenticationFactory
        $providerId = 'security.authentication.provider.guard.auth0.'.$id;
        $container
            ->setDefinition($providerId, new ChildDefinition('security.authentication.provider.guard'))
            ->replaceArgument(0, $authenticators)
            ->replaceArgument(1, $userProvider)
            ->replaceArgument(2, $id)
            ->replaceArgument(3, new Reference('security.user_checker.'.$id))
        ;

        // listener
        $listenerId = 'security.authentication.listener.guard.auth0.'.$id;
        $listener = $container->setDefinition($listenerId, new ChildDefinition('security.authentication.listener.guard'));
        $listener->replaceArgument(2, $id);
        $listener->replaceArgument(3, $authenticators);

        // determine the entryPointId to use
        $entryPointAlias = 'rmlev_auth0_login.entry_point.'.$id;
        $container
            ->setAlias($entryPointAlias, $authenticatorId);
        $entryPointId = $defaultEntryPointId ?? $entryPointAlias;

        // this is always injected - then the listener decides if it should be used
        $container
            ->getDefinition($listenerId)
            ->addTag('security.remember_me_aware', ['id' => $id, 'provider' => $userProviderId]);

        if ($this->isNewSecuritySystem() === false) {
            $this->addLogoutHandler($container, $id, $config);
        }

        return [$providerId, $listenerId, $entryPointId];
    }

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string
    {
        $this->setOptions($container, $firewallName, $config);
        $authenticatorId = self::BASE_AUTHENTICATOR_SERVICE_ID.'.'.$firewallName;

        $container
            ->setDefinition($authenticatorId, new ChildDefinition(self::BASE_AUTHENTICATOR_SERVICE_ID))
            ->setArgument('$successHandler', new Reference($this->createAuthenticationSuccessHandler($container, $firewallName, $config)))
            ->setArgument('$failureHandler', new Reference($this->createAuthenticationFailureHandler($container, $firewallName, $config)))
            ->setArgument('$options', $this->getOptions($config))
            ->setArgument('$userProvider', $this->userProviderFactory->createAuth0UserProvider($container, $firewallName, $config, $userProviderId))
        ;

        $container
            ->setAlias('rmlev_auth0_login.entry_point.'.$firewallName, $authenticatorId);

        return $authenticatorId;
    }

    /**
     * @inheritDoc
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId): ?string
    {
        // Doesn't use parent::create
        return null;
    }

    /**
     * @inheritDoc
     */
    protected function getListenerId(): ?string
    {
        // Doesn't use parent::create
        return null;
    }

    private function addLogoutHandler(ContainerBuilder $container, string $firewallName, array $config): void
    {
        $logoutListenerId = 'security.logout_listener.'.$firewallName;
        if ($container->hasDefinition($logoutListenerId) && interface_exists(LogoutSuccessHandlerInterface::class)) {
            $logoutListener = $container->getDefinition($logoutListenerId);

            $auth0logoutSuccessHandler = new Reference('rmlev_auth0_login.security_core_logout.auth0logout_success_handler');
            $logoutListener->replaceArgument(2, $auth0logoutSuccessHandler);
        }
    }

    private function setOptions(ContainerBuilder $container, string $firewallName, array $config): void
    {
        $optionsDefinition = new Definition(Auth0Options::class);
        $optionsDefinition
            ->setArgument(0, $this->getOptions($config));

        $optionsDefinition->addTag('rmlev_auth0_login_options', ['firewall' => $firewallName]);

        $container->setDefinition('rmlev_auth0_login.helper.auth0options.'.$firewallName, $optionsDefinition);
    }

    /**
     * @param array $config
     * @return array
     */
    private function getOptions(array $config): array
    {
        return array_intersect_key($config, array_merge(['login_path' => '/login'], $this->options));
    }

    private function isNewSecuritySystem(): bool
    {
        return interface_exists(AuthenticatorFactoryInterface::class);
    }
}
