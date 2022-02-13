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
use Rmlev\Auth0LoginBundle\Helper\Auth0OptionsCollection;

final class Auth0OptionsCollectionTest extends TestCase
{
    /**
     * @dataProvider providerGetOptions
     */
    public function testOptions(array $options): void
    {
        $options1 = $options[0];
        $options2 = $options[1];

        $collection = new Auth0OptionsCollection();
        $auth0Options1 = new Auth0Options($options1);
        $auth0Options2 = new Auth0Options($options2);

        $collection->addOptions($auth0Options1, 'stub1');
        $collection->addOptions($auth0Options2, 'stub2');

        $this->assertSame($options1, $collection->getOptions('stub1'));
        $this->assertSame($options2, $collection->getOptions('stub2'));
    }

    /**
     * @dataProvider providerGetOptions
     */
    public function testOptionsFirewallNotFound(array $options): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Configuration for firewall "stub" is not found.');

        $options1 = $options[0];
        $options2 = $options[1];

        $collection = new Auth0OptionsCollection();
        $auth0Options1 = new Auth0Options($options1);
        $auth0Options2 = new Auth0Options($options2);

        $collection->addOptions($auth0Options1, 'stub1');
        $collection->addOptions($auth0Options2, 'stub2');

        $collection->getOptions('stub');
    }

    /**
     * @dataProvider providerGetOptions
     */
    public function testGetAmbiguousOptions(array $options): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ambiguous option. The firewall name is not specified.');

        $options1 = $options[0];
        $options2 = $options[1];

        $collection = new Auth0OptionsCollection();
        $auth0Options1 = new Auth0Options($options1);
        $auth0Options2 = new Auth0Options($options2);

        $collection->addOptions($auth0Options1, 'stub1');
        $collection->addOptions($auth0Options2, 'stub2');

        $collection->getOptions();
    }

    /**
     * @dataProvider providerGetOptions
     */
    public function testGetSingleOptions(array $options): void
    {
        $options1 = $options[0];

        $collection = new Auth0OptionsCollection();
        $auth0Options1 = new Auth0Options($options1);

        $collection->addOptions($auth0Options1, 'stub1');

        $this->assertSame($options1, $collection->getOptions('stub1'));
        $this->assertSame($options1, $collection->getOptions());
    }

    public function providerGetOptions(): \Generator
    {
        yield [[
            [
                'option1_1' => 'value1_1',
                'option2_1' => 'value2_1',
                'option3_1' => 'value3_1',
            ],
            [
                'option1_2' => 'value1_2',
                'option2_2' => 'value2_2',
                'option3_2' => 'value3_2',
            ],
        ]];
    }

    public function testGetEmptyOptions(): void
    {
        $collection = new Auth0OptionsCollection();

        $this->assertSame([], $collection->getOptions());
    }
}
