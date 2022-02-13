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

final class Auth0OptionsCollection
{
    private array $options = [];

    public function addOptions(Auth0Options $options, string $firewallName): void
    {
        $this->options[$firewallName] = $options;
    }

    public function getOptions(string $firewallName = null): array
    {
        if ($firewallName === null) {
            if (count($this->options) === 0) {
                return [];
            }

            if (count($this->options) === 1) {
                foreach ($this->options as $firewallOptions) {
                    return $firewallOptions->getOptions();
                }
            }

            throw new \Exception('Ambiguous option. The firewall name is not specified.');
        }

        if (array_key_exists($firewallName, $this->options) === false) {
            throw new \Exception(sprintf('Configuration for firewall "%s" is not found.', $firewallName));
        }

        return $this->options[$firewallName]->getOptions();
    }
}
