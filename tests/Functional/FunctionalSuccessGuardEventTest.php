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

namespace Rmlev\Auth0LoginBundle\Tests\Functional;

use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

final class FunctionalSuccessGuardEventTest extends FunctionalSuccessEventTest
{
    protected bool $newSecuritySystem = false;

    protected static array $kernelOptions = [
        'security' => 'security_guard.yaml',
        'extra' => ['success_listener.yaml'],
    ];

    protected function setUp(): void
    {
        if (class_exists(AbstractGuardAuthenticator::class) === false) {
            /** @phpstan-ignore-next-line  */
            $this->markTestSkipped(sprintf('Class "%s" not found', AbstractGuardAuthenticator::class));
        }

        parent::setUp();
    }
}
