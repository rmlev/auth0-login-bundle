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

final class FunctionalProtectedAreaUseForwardTest extends FunctionalProtectedAreaTest
{
    protected bool $useForward = true;

    protected static array $kernelOptions = [
        'security' => 'security_use_forward.yaml'
    ];
}
