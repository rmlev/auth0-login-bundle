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

namespace Rmlev\Auth0LoginBundle\Tests\Functional\App\src\Connector\Auth0;

use Auth0\SDK\Contract\Auth0Interface;
use PHPUnit\Framework\TestCase;

final class StubAuth0Factory extends TestCase
{
    public function createAuth0Stub(): Auth0Interface
    {
        return $this->createStub(Auth0Interface::class);
    }
}
