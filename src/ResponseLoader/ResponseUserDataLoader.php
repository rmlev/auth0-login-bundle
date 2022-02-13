<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\ResponseLoader;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ResponseUserDataLoader implements ResponseUserDataLoaderInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    /**
     * Identifier for a User class in Symfony security system.
     *
     * Configurable in security.yaml under firewall key.
     *
     *     user_data_loader:
     *         default:
     *             identifier:
     *
     * @var string
     */
    private string $identifierUserSide = 'email';

    /**
     * User identifier on Auth0 server. Used to check User entity.
     *
     * Configurable in security.yaml under firewall key.
     *
     *     user_data_loader:
     *         default:
     *             identifier_auth0:
     *
     * @var string
     */
    private string $keyAuth0Side = 'sub';

    /**
     * Mapping between Auth0 and Symfony User properties
     *
     * Configurable in security.yaml under firewall key.
     *
     *     user_data_loader:
     *         default:
     *             map_options:
     *
     * @var array|string[]
     */
    private array $mapOptions = [
        'nickname' => 'nickname',
        'name' => 'name',
        'email' => 'email',
        'sub' => 'auth0_user_key',
        'picture' => 'avatar'
    ];

    public function __construct(PropertyAccessorInterface $propertyAccessor, string $identifier = null, string $auth0Key = null, array $mapOptions = [])
    {
        $this->propertyAccessor = $propertyAccessor;
        if ($identifier !== null) {
            $this->setIdentifierUserSide($identifier);
        }
        if ($auth0Key !== null) {
            $this->setKeyAuth0Side($auth0Key);
        }
        $this->mapOptions = array_merge($this->mapOptions, $mapOptions);
    }

    private function getIdentifierUserSide(): string
    {
        return $this->identifierUserSide;
    }

    public function setIdentifierUserSide(string $identifierUserSide): void
    {
        $this->identifierUserSide = $identifierUserSide;
    }

    public function getMapOptions(): array
    {
        return $this->mapOptions;
    }

    public function setMapOptions(array $mapOptions): void
    {
        $this->mapOptions = $mapOptions;
    }

    public function addMapOptions(array $mapOptions): void
    {
        $this->mapOptions = array_merge($this->mapOptions, $mapOptions);
    }

    public function getIdentifierAuth0Side(): string
    {
        $idProp = $this->getIdentifierUserSide();
        $map = array_flip($this->getMapOptions());

        return $map[$idProp];
    }

    private function getKeyUserSide(): string
    {
        return $this->mapOptions[$this->getKeyAuth0Side()];
    }

    public function loadUserProperties(UserInterface $user, ParameterBag $userResponseData): UserInterface
    {
        foreach ($this->mapOptions as $responseMap => $userProperty) {
            if ($userResponseData->has($responseMap)) {
                $this->propertyAccessor
                    ->setValue($user, $userProperty, $userResponseData->get($responseMap));
            }
        }

        return $user;
    }

    public function checkUserProperties(UserInterface $user, ParameterBag $userResponseData): bool
    {
        /** @phpstan-ignore-next-line  */
        if ($user->getUserIdentifier() !==
            $userResponseData->get($this->getIdentifierAuth0Side())
        ) {
            return false;
        }
        if ($this->propertyAccessor->getValue($user, $this->getKeyUserSide()) !==
            $userResponseData->get($this->getKeyAuth0Side())
        ) {
            return false;
        }

        return true;
    }

    public function checkUserIdentifier(string $identifier, ParameterBag $userResponseData): bool
    {
        if ($identifier !== $userResponseData->get($this->getIdentifierAuth0Side())) {
            return false;
        }

        return true;
    }

    private function getKeyAuth0Side(): string
    {
        return $this->keyAuth0Side;
    }

    public function setKeyAuth0Side(string $keyAuth0Side): void
    {
        $this->keyAuth0Side = $keyAuth0Side;
    }
}
