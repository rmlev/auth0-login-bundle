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

namespace Rmlev\Auth0LoginBundle\Tests\Functional\App\src\EventListener;

use Rmlev\Auth0LoginBundle\Event\ConnectFailureEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\HttpUtils;

final class FailureListener
{
    private HttpUtils $httpUtils;
    private RequestStack $requestStack;

    public function __construct(HttpUtils $httpUtils, RequestStack $requestStack)
    {
        $this->httpUtils = $httpUtils;
        $this->requestStack = $requestStack;
    }

    public function onConnectFailureEvent(ConnectFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $response = $this->httpUtils->createRedirectResponse($request, 'app_event_failure');
        $event->setResponse($response);

        $session = $this->requestStack->getSession();
        $session->set(Security::AUTHENTICATION_ERROR, $event->getException());
    }
}
