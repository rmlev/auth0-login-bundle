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

namespace Rmlev\Auth0LoginBundle\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\Helper\Auth0Options;

final class Auth0OptionsTest extends TestCase
{
    public function testGetOptions(): void
    {
        $options = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => 'value4',
        ];

        $auth0Options = new Auth0Options($options);

        $this->assertSame($options, $auth0Options->getOptions());
    }
}
