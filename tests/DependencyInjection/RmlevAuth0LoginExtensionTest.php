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

namespace Rmlev\Auth0LoginBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\DependencyInjection\RmlevAuth0LoginExtension;
use Rmlev\Auth0LoginBundle\Connector\Auth0\Factory\Auth0Factory;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0UserProvider;
use Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Auth0Authenticator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RmlevAuth0LoginExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $configs = [[
            'domain' => 'test.domain.com',
            'client_id' => '9d4cdaddec93ece7a1eaa961bcf198cadec6115c369f582309d20acf5ff3d4f2',
            'client_secret' => '9d4cdaddec93ece7a1eaa961bcf198cadec6115c369f582309d20acf5ff3d4f2',
            'cookie_secret' => '49ee60461b17740a80bc6a1946229e3904c98ff1b0dbdda8e59509c9be3b4095',
        ]];

        $extension = new RmlevAuth0LoginExtension();
        $extension->load($configs, $container);

        $auth0FactoryDefinition = $container->getDefinition('rmlev_auth0_login.connector_auth0_factory.auth0factory');
        $this->assertSame(Auth0Factory::class, $auth0FactoryDefinition->getClass());
        $this->assertSame($configs[0]['domain'], $auth0FactoryDefinition->getArgument(0));
        $this->assertSame($configs[0]['client_id'], $auth0FactoryDefinition->getArgument(1));
        $this->assertSame($configs[0]['client_secret'], $auth0FactoryDefinition->getArgument(2));
        $this->assertSame($configs[0]['cookie_secret'], $auth0FactoryDefinition->getArgument(3));

        $auth0HelperDefinition = $container->getDefinition('rmlev_auth0_login.helper.auth0helper');
        $this->assertSame(Auth0Helper::class, $auth0HelperDefinition->getClass());

        $auth0AuthenticatorDefinition = $container->getDefinition('rmlev_auth0_login.security.authenticator');
        $this->assertSame(Auth0Authenticator::class, $auth0AuthenticatorDefinition->getClass());

        $auth0AuthenticatorDefinition = $container->getDefinition('rmlev_auth0_login.security.user.provider');
        $this->assertSame(Auth0UserProvider::class, $auth0AuthenticatorDefinition->getClass());
    }
}
