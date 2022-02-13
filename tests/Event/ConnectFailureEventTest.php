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

namespace Rmlev\Auth0LoginBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Event\ConnectFailureEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class ConnectFailureEventTest extends TestCase
{
    /**
     * @dataProvider connectFailureDataProvider
     */
    public function testGetRequest(Request $request, AuthenticationException $exception): void
    {
        $connectFailureEvent = new ConnectFailureEvent($request, $exception);
        $this->assertSame($request, $connectFailureEvent->getRequest());
    }

    /**
     * @dataProvider connectFailureDataProvider
     */
    public function testGetException(Request $request, AuthenticationException $exception): void
    {
        $connectFailureEvent = new ConnectFailureEvent($request, $exception);
        $this->assertSame($exception, $connectFailureEvent->getException());
    }

    /**
     * @dataProvider connectFailureDataProvider
     */
    public function testResponse(Request $request, AuthenticationException $exception): void
    {
        $connectFailureEvent = new ConnectFailureEvent($request, $exception);

        $response = new RedirectResponse('https://example.com/stub');
        $connectFailureEvent->setResponse($response);
        $this->assertSame($response, $connectFailureEvent->getResponse());
    }

    public function connectFailureDataProvider(): \Generator
    {
        yield [
            Request::create('https://example.com'),
            new AuthenticationException(),
        ];

        yield [
            Request::create('https://example.com/stub/url'),
            new AuthenticationException('Some exception'),
        ];
    }
}
