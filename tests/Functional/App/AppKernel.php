<?php

declare(strict_types=1);

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Tests\Functional\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Rmlev\Auth0LoginBundle\RmlevAuth0LoginBundle;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\SessionPass;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorageFactory;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\PhpFileLoader as RoutingPhpFileLoader;
use Symfony\Component\Routing\RouteCollection;

final class AppKernel extends Kernel
{
    private array $options;

    private bool $isNewSecuritySystem;

    public function __construct(string $environment, bool $debug, array $options = [])
    {
        parent::__construct($environment, $debug);

        $this->isNewSecuritySystem = interface_exists(AuthenticatorFactoryInterface::class);
        $this->options = $options;
        $this->options['security'] = $options['security'] ?? 'security.yaml';
        $this->options['success'] = $options['success'] ?? true;
        $this->options['extra'] = $options['extra'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new SecurityBundle(),
            new RmlevAuth0LoginBundle(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $testConfigDir = $this->getTestAppConfigDir();
        if (class_exists( SessionPass::class) === false) {
            $loader->load($testConfigDir . 'packages/framework.yaml');
        } else {
            $loader->load($testConfigDir . 'packages/framework_legacy.yaml');
        }
        $loader->load($testConfigDir . 'packages/doctrine.yaml');
        $loader->load($testConfigDir . 'packages/' . $this->options['security']);
        $loader->load($testConfigDir . 'packages/rmlev_auth0_login.yaml');
        $loader->load($testConfigDir . 'services_test.yaml');
        if ($this->options['success']) {
            $loader->load($testConfigDir . 'connector.php');
        } else {
            $loader->load($testConfigDir . 'connector_failure.php');
        }
        foreach ($this->options['extra'] as $resource) {
            $loader->load($testConfigDir . $resource);
        }

        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type' => 'service',
                ],
            ]);

            $kernelClass = false !== strpos(static::class, "@anonymous\0") ? parent::class : static::class;

            if (!$container->hasDefinition('kernel')) {
                $container->register('kernel', $kernelClass)
                    ->addTag('controller.service_arguments')
                    ->setAutoconfigured(true)
                    ->setSynthetic(true)
                    ->setPublic(true)
                ;
            }

            $kernelDefinition = $container->getDefinition('kernel');
            $kernelDefinition->addTag('routing.route_loader');
        });
    }

    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        $collection = new RouteCollection();
        $file = (new \ReflectionObject($this))->getFileName();

        /** @var RoutingPhpFileLoader $kernelLoader */
        $kernelLoader = $loader->getResolver()->resolve($file, 'php');
        $kernelLoader->setCurrentDir(\dirname($file));
        $routingConfigurator = new RoutingConfigurator($collection, $kernelLoader, $file, $file, $this->getEnvironment());

        $this->configureRoutes($routingConfigurator);

        return $collection;
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $configDir = $this->getConfigDir();
        $testConfigDir = $this->getTestAppConfigDir();
        $routes->import($configDir . '/routes.xml')
            ->prefix('/auth0');
        $routes->import($testConfigDir . '/routes/routing.yaml');
    }

    private function getConfigDir(): string
    {
        return $this->getProjectDir().'/config/';
    }

    private function getTestAppConfigDir(): string
    {
        if ($this->isNewSecuritySystem === false) {
            return __DIR__ . '/config_legacy/';
        }

        return __DIR__ . '/config/';
    }
}
