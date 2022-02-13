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

use Rmlev\Auth0LoginBundle\Tests\Functional\App\AppKernel;

require __DIR__ . '/../vendor/autoload.php';

$kernel = new AppKernel('test', true);
$kernel->boot();
return $kernel->getContainer()->get('doctrine')->getManager();
