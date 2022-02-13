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

namespace Rmlev\Auth0LoginBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\DependencyInjection\Compiler\OptionsCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class YamlOptionsCompilerPassTest extends TestCase
{
    private function loadFromFile(ContainerBuilder $container, string $file): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Fixtures/yaml'));
        $loader->load($file.'.yaml');
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $this->loadFromFile($container, 'options');

        $compilerPass = new OptionsCompilerPass();
        $compilerPass->process($container);

        $auth0CollectionDef = $container->getDefinition('options_collection');
        $this->assertSame(1, count($auth0CollectionDef->getMethodCalls()));
        $this->assertEquals(
            [
                'addOptions',
                [new Reference('options.stub'), 'stub_firewall']
            ],
            $auth0CollectionDef->getMethodCalls()[0]
        );
    }
}
