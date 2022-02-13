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

namespace Rmlev\Auth0LoginBundle\Tests\Functional\App\src\Controller;

use Rmlev\Auth0LoginBundle\Security\Core\User\Auth0User;
use Rmlev\Auth0LoginBundle\Tests\Fixtures\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class TestController
{
    private TokenStorageInterface $tokenStorage;
    /** @phpstan-ignore-next-line  */
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function homepageAction(): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        if ($token) {
            /** @var Auth0User|User|string|null $user */
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $userData = [
                    'class' => get_class($user),
                    'email' => $user->getEmail(),
                    'nickname' => $user->getNickname(),
                    'name' => $user->getName(),
                    'auth0_key' => $user->getAuth0UserKey(),
                    'avatar' => $user->getAvatar(),
                ];
            } else {
                $userData = $user;
            }
            $data = [
                'token' => get_class($token),
                'user' => $userData,
            ];
        } else {
            $data = [
                'token' => null,
                'user' => null,
            ];
        }
        $data['message'] = 'This is the homepage';

        return new JsonResponse($data);
    }

    public function protectedAreaAction(): Response
    {
        return new Response('<h1>This is a protected page</h1>');
    }

    public function successAction(): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var Auth0User|User $user */
        $user = $token->getUser();
        $userData = [
            'email' => $user->getUserIdentifier(),
            'nickname' => $user->getNickname(),
            'name' => $user->getName(),
            'auth0_key' => $user->getAuth0UserKey(),
            'avatar' => $user->getAvatar(),
        ];
        $data = [
            'message' => 'This is the successful authentication page',
            'user' => $userData,
        ];

        return new JsonResponse($data);
    }

    public function successEventAction(): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var Auth0User|User $user */
        $user = $token->getUser();
        $data = [
            'message' => 'This is redirect on success event',
            'token' => get_class($token),
            'user' => [
                'email' => $user->getUserIdentifier(),
                'nickname' => $user->getNickname(),
                'name' => $user->getName(),
                'auth0_key' => $user->getAuth0UserKey(),
                'avatar' => $user->getAvatar(),
            ],
        ];

        return new JsonResponse($data);
    }

    public function successLogoutAction(): Response
    {
        return new Response('This is redirect on success logout');
    }

    public function failureEventAction(): Response
    {
        return new Response('This is redirect on failure event');
    }

    public function onFailureAction(): Response
    {
        return new Response('Authentication failure');
    }

    public function stub1SuccessAction(): Response
    {
        return new Response('This is redirect on success login for "stub1" firewall');
    }

    public function stub2SuccessAction(): Response
    {
        return new Response('This is redirect on success login for "stub2" firewall');
    }

    public function stub1OnLogoutAction(): Response
    {
        return new Response('This is redirect on logout for "stub1" firewall');
    }

    public function stub2OnLogoutAction(): Response
    {
        return new Response('This is redirect on logout for "stub2" firewall');
    }
}
