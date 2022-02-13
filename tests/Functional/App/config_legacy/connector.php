<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Rmlev\Auth0LoginBundle\Tests\Functional\App\src\Connector\Auth0\StubConnector;

return function(ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->set('rmlev_auth0_login.connector_auth0.auth0wrapper', StubConnector::class)
        ->arg(
            '$userData',
            [
                'email' => 'stub@example.com',
                'nickname' => 'stub',
                'name' => 'stub@example.com',
                'email_verified' => true,
                'sub' => 'abcd.123',
                'picture' => 'https://stub.avatar.com/avatar/12345.png',
            ]
        )
        ->arg('$idToken', 'idToken.stub')
        ->arg('$accessToken', 'accessToken.stub')
        ->arg('$refreshToken', 'accessToken.stub')
    ;
};
