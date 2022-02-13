<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Security\Guard\Authenticator;

use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\OAuthCredentialsInterface;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\Event\ConnectFailureEvent;
use Rmlev\Auth0LoginBundle\Event\ConnectSuccessEvent;
use Rmlev\Auth0LoginBundle\Security\Core\User\BaseUserProvider;
use Rmlev\Auth0LoginBundle\Security\Guard\Token\Auth0GuardToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class Auth0GuardAuthenticator extends AbstractGuardAuthenticator
{
    private HttpKernelInterface $httpKernel;
    private ConnectorInterface $connector;
    private EventDispatcherInterface $eventDispatcher;
    private HttpUtils $httpUtils;
    private Auth0Helper $auth0Helper;
    private AuthenticationSuccessHandlerInterface $successHandler;
    private AuthenticationFailureHandlerInterface $failureHandler;
    private array $options;

    private BaseUserProvider $userProvider;
    private ?OAuthCredentialsInterface $auth0Credentials = null;

    public function __construct(
        HttpKernelInterface                   $httpKernel,
        ConnectorInterface                    $connector,
        EventDispatcherInterface              $eventDispatcher,
        HttpUtils                             $httpUtils,
        Auth0Helper                           $auth0Helper,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array                                 $options,
        BaseUserProvider                      $userProvider
    )
    {
        $this->httpKernel = $httpKernel;
        $this->connector = $connector;
        $this->eventDispatcher = $eventDispatcher;
        $this->httpUtils = $httpUtils;
        $this->auth0Helper = $auth0Helper;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->options = $options;
        $this->userProvider = $userProvider;
    }

    /**
     * @inheritDoc
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        if ($this->options['use_forward']) {
            $subRequest = $this->httpUtils->createRequest($request, $this->options['login_path']);

            /** @var \ArrayIterator $iterator */
            $iterator = $request->query->getIterator();
            $subRequest->query->add($iterator->getArrayCopy());

            $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
            if (200 === $response->getStatusCode()) {
                $response->setStatusCode(401);
            }

            return $response;
        }
        return $this->httpUtils->createRedirectResponse($request, $this->options['login_path']);
    }

    /**
     * @inheritDoc
     */
    public function supports(Request $request): bool
    {
        return $this->auth0Helper->supports($request);
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(Request $request)
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');
        $redirectURI = $this->httpUtils->generateUri($request, $this->options['check_path']);

        return [
            'code' => $code,
            'state' => $state,
            'redirect_uri' => $redirectURI,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $success = $this->connector->exchange($credentials['redirect_uri'], $credentials['code'], $credentials['state']);
        if (!$success) {
            return null;
        }

        $auth0Credentials = $this->connector->getCredentials();
        if (!$auth0Credentials) {
            throw $this->auth0Helper->createUserNotFoundException('User not found.');
        }
        $this->setAuth0Credentials($auth0Credentials);

        return $this->userProvider->loadUserFromAuth0Response($auth0Credentials->getUserData());
    }

    public function createAuthenticatedToken(UserInterface $user, $providerKey): Auth0GuardToken
    {
        return new Auth0GuardToken($user, $providerKey, $this->getAuth0Credentials()->getTokenAttributes());
    }

    /**
     * @inheritDoc
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        /** @phpstan-ignore-next-line  */
        if ($user->getUserIdentifier()) {
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->eventDispatcher->dispatch($connectFailureEvent = new ConnectFailureEvent($request, $exception));
        if ($response = $connectFailureEvent->getResponse()) {
            return $response;
        }

        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        $this->eventDispatcher->dispatch($connectSuccessEvent = new ConnectSuccessEvent($token, $request, $providerKey));
        if ($response = $connectSuccessEvent->getResponse()) {
            return $response;
        }

        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    /**
     * @inheritDoc
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }

    private function getAuth0Credentials(): OAuthCredentialsInterface
    {
        if ($this->auth0Credentials === null) {
            throw new AuthenticationException('User is not authenticated');
        }

        return $this->auth0Credentials;
    }

    private function setAuth0Credentials(OAuthCredentialsInterface $auth0Credentials): void
    {
        $this->auth0Credentials = $auth0Credentials;
    }
}
