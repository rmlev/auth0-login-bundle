<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Rmlev\Auth0LoginBundle\Tests\Functional\App\src\Connector\Auth0\StubFailureConnector;

return function(ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->set('rmlev_auth0_login.connector_auth0.auth0wrapper', StubFailureConnector::class);
};
