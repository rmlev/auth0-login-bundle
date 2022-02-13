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
use Rmlev\Auth0LoginBundle\Helper\Auth0Options;
use Rmlev\Auth0LoginBundle\Helper\Auth0OptionsCollection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class OptionsCompilerPassTest extends TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();

        $optionsDefinition = new Definition(Auth0Options::class);
        $optionsDefinition->addMethodCall('addOptions', [['stub' => 'value'], 'stub']);
        $optionsDefinition->addTag('rmlev_auth0_login_options', ['firewall' => 'stub']);
        $container->setDefinition('options', $optionsDefinition);

        $optionsCollectionDefinition = new Definition(Auth0OptionsCollection::class);
        $optionsCollectionDefinition->addTag('rmlev_auth0_login_options_collection');
        $container->setDefinition('options_collection', $optionsCollectionDefinition);

        $compilerPass = new OptionsCompilerPass();
        $compilerPass->process($container);

        $collectionDef = $container->getDefinition('options_collection');
        $this->assertSame(1, count($collectionDef->getMethodCalls()));
        $this->assertEquals(
            [
                'addOptions',
                [new Reference('options'), 'stub']
            ],
            $collectionDef->getMethodCalls()[0]
        );
    }
}
