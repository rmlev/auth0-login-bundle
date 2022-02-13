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

namespace Rmlev\Auth0LoginBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Rmlev\Auth0LoginBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class ConfigurationTest extends TestCase
{
    public function testProcessConfigSettings(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, [
            [
                'domain' => 'test.domain.com',
                'client_id' => '9d4cdaddec93ece7a1eaa961bcf198cadec6115c369f582309d20acf5ff3d4f2',
                'client_secret' => '9d4cdaddec93ece7a1eaa961bcf198cadec6115c369f582309d20acf5ff3d4f2',
                'cookie_secret' => '49ee60461b17740a80bc6a1946229e3904c98ff1b0dbdda8e59509c9be3b4095',
            ],
            [
                'client_id' => '414fa809e0d6de8305f057b80fb548b4dd6e4a70dcc05d0b36a69313f61d5493',
            ]
        ]);

        $this->assertEquals(
            [
                'domain' => 'test.domain.com',
                'client_id' => '414fa809e0d6de8305f057b80fb548b4dd6e4a70dcc05d0b36a69313f61d5493',
                'client_secret' => '9d4cdaddec93ece7a1eaa961bcf198cadec6115c369f582309d20acf5ff3d4f2',
                'cookie_secret' => '49ee60461b17740a80bc6a1946229e3904c98ff1b0dbdda8e59509c9be3b4095',
            ],
            $config
        );
    }

    public function testProcessEmptyConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $processor = new Processor();
        $configuration = new Configuration();
        $processor->processConfiguration($configuration, [
            []
        ]);
    }

    public function testProcessPartialEmptyConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $processor = new Processor();
        $configuration = new Configuration();
        $processor->processConfiguration($configuration, [
            [
                'domain' => 'test.domain.com',
                'client_id' => '9d4cdaddec93ece7a1eaa961bcf198cadec6115c369f582309d20acf5ff3d4f2',
                'client_secret' => '9d4cdaddec93ece7a1eaa961bcf198cadec6115c369f582309d20acf5ff3d4f2',
            ]
        ]);
    }
}
