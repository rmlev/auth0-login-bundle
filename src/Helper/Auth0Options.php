<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Helper;

final class Auth0Options
{
    private array $options = [];

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
