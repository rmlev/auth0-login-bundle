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

namespace Rmlev\Auth0LoginBundle\Tests\Integration;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class YamlAuth0SecurityExtensionTest extends BaseAuth0SecurityExtensionTest
{
    protected function loadFromFile(ContainerBuilder $container, string $file, bool $valid = true): void
    {
        $newAuthSystem = $this->isNewSecuritySystem();
        if ($newAuthSystem) {
            $path = $valid ? __DIR__ . '/Fixtures/yaml' : __DIR__ . '/Fixtures/yaml/invalid';
        } else {
            $path = $valid ? __DIR__ . '/Fixtures/yaml/legacy' : __DIR__ . '/Fixtures/yaml/legacy/invalid';
        }

        $loader = new YamlFileLoader($container, new FileLocator($path));
        $loader->load($file.'.yaml');
    }
}
