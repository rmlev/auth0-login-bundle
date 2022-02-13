<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Security\Core\User;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\ResponseLoader\ResponseUserDataLoaderInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class EntityUserProvider extends BaseUserProvider
{
    private ManagerRegistry $registry;
    protected ConnectorInterface $connector;
    protected Auth0Helper $auth0Helper;
    private string $classOrAlias;
    private ?string $property;
    private ?string $managerName;
    private ?string $class = null;

    public function __construct(ManagerRegistry $registry, ConnectorInterface $connector, Auth0Helper $auth0Helper, ResponseUserDataLoaderInterface $responseUserDataLoader, string $classOrAlias, string $property = null, string $managerName = null)
    {
        parent::__construct($connector, $auth0Helper, $responseUserDataLoader);

        $this->registry = $registry;
        $this->connector = $connector;
        $this->auth0Helper = $auth0Helper;
        $this->classOrAlias = $classOrAlias;
        $this->property = $property;
        $this->managerName = $managerName;
    }

    /**
     * @param string $identifier
     * @return UserInterface
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $repository = $this->getRepository();
        if (null !== $this->property) {
            /** @var UserInterface $user */
            $user = $repository->findOneBy([$this->property => $identifier]);
        } else {
            if (!$repository instanceof UserLoaderInterface) {
                throw new \InvalidArgumentException(sprintf('You must either make the "%s" entity Doctrine Repository ("%s") implement "Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface" or set the "property" option in the corresponding entity provider configuration.', $this->classOrAlias, get_debug_type($repository)));
            }

            if (method_exists($repository, 'loadUserByIdentifier')) {
                $user = $repository->loadUserByIdentifier($identifier);
            } else {
                /** @phpstan-ignore-next-line  */
                $user = $repository->loadUserByUsername($identifier);
            }
        }

        if ($user === null) {
            throw $this->auth0Helper->createUserNotFoundException(sprintf('User "%s" not found.', $identifier), $identifier);
        }

        return $user;
    }

    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user)
    {
        $class = $this->getClass();
        if (!$user instanceof $class) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_debug_type($user)));
        }

        $repository = $this->getRepository();
        if ($repository instanceof UserProviderInterface) {
            $refreshedUser = $repository->refreshUser($user);
        } else {
            if (!$id = $this->getClassMetadata()->getIdentifierValues($user)) {
                throw new \InvalidArgumentException('You cannot refresh a user from the EntityUserProvider that does not contain an identifier. The user object has to be serialized with its own identifier mapped by Doctrine.');
            }

            $refreshedUser = $repository->find($id);
            if (null === $refreshedUser) {
                throw $this->auth0Helper->createUserNotFoundException(sprintf('User with id  "%s" not found.', json_encode($id)), json_encode($id));
            }
        }

        return $refreshedUser;
    }

    public function supportsClass($class): bool
    {
        return $class === $this->getClass() || is_subclass_of($class, $this->getClass());
    }

    public function loadUserByIdentifierFromAuth0Response(string $identifier): ?UserInterface
    {
        $userData = $this->loadUserData($identifier);
        return $this->loadUser($identifier, $userData);
    }

    protected function loadUser(string $identifier, ParameterBag $userData): ?UserInterface
    {
        try {
            $user = $this->loadUserByIdentifier($identifier);

            if (!$this->responseUserDataLoader->checkUserProperties($user, $userData)) {
                throw $this->auth0Helper->createUserNotFoundException('User '.$identifier.' not found', $identifier);
            }
        } catch (AuthenticationException $exception) {
            $userClass = $this->getClass();
            $user = new $userClass();

            $this->responseUserDataLoader->loadUserProperties($user, $userData);
            $objectManager = $this->getObjectManager();
            $objectManager->persist($user);
            $objectManager->flush();

            return $user;
        }

        return $user;
    }

    private function getClass(): string
    {
        if (null === $this->class) {
            $class = $this->classOrAlias;

            if (str_contains($class, ':')) {
                $class = $this->getClassMetadata()->getName();
            }

            $this->class = $class;
        }

        return $this->class;
    }

    private function getClassMetadata(): ClassMetadata
    {
        return $this->getObjectManager()->getClassMetadata($this->classOrAlias);
    }

    private function getObjectManager(): ObjectManager
    {
        return $this->registry->getManager($this->managerName);
    }

    private function getRepository(): ObjectRepository
    {
        return $this->getObjectManager()->getRepository($this->classOrAlias);
    }
}
