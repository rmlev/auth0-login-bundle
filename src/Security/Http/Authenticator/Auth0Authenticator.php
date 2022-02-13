<?php

/*
 * This file is part of the Auth0LoginBundle package.
 *
 * (c) Roman Levchenko <rlev0109@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rmlev\Auth0LoginBundle\Security\Http\Authenticator;

use Rmlev\Auth0LoginBundle\Connector\Auth0\Credentials\OAuthCredentialsInterface;
use Rmlev\Auth0LoginBundle\Connector\ConnectorInterface;
use Rmlev\Auth0LoginBundle\Helper\Auth0Helper;
use Rmlev\Auth0LoginBundle\Event\ConnectFailureEvent;
use Rmlev\Auth0LoginBundle\Event\ConnectSuccessEvent;
use Rmlev\Auth0LoginBundle\Security\Core\User\BaseUserProvider;
use Rmlev\Auth0LoginBundle\Security\Http\Authenticator\Token\Auth0Token;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\UserPassportInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class Auth0Authenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
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
    private ?OAuthCredentialsInterface $credentials = null;

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

    public function supports(Request $request): bool
    {
        return $this->auth0Helper->supports($request);
    }

    public function authenticate(Request $request): Passport
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');

        $redirectURI = $this->httpUtils->generateUri($request, $this->options['check_path']);

        $success = $this->connector->exchange($redirectURI, $code, $state);
        $credentials = $this->connector->getCredentials();
        if (!$credentials) {
            throw $this->auth0Helper->createUserNotFoundException('User not found.');
        }
        $this->setCredentials($credentials);

        if (class_exists(UserBadge::class)) {
            $userBadge = new UserBadge(
                $this->userProvider->getUserIdentifier($credentials->getUserData()),
                [$this->userProvider, 'loadUserByIdentifierFromAuth0Response']
            );
        } else {
            $userBadge = $this->userProvider->loadUserFromAuth0Response($credentials->getUserData());
        }

        $passport = new SelfValidatingPassport(
            $userBadge
        );

        if (method_exists($passport, 'setAttribute')) {
            $passport->setAttribute('access_token', $credentials->getAccessToken());
            $passport->setAttribute('id_token', $credentials->getIdToken());
            $passport->setAttribute('refresh_token', $credentials->getRefreshToken());
            $passport->setAttribute(
                'expires_at',
                new \DateTimeImmutable('@'.$credentials->getAccessTokenExpiration())
            );
        }

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $tokenAttributes = null;
        // For Symfony 5.1
        if (!method_exists($passport, 'setAttribute')) {
            $tokenAttributes = $this->getCredentials()->getTokenAttributes();
        }

        return new Auth0Token($passport, $firewallName, $tokenAttributes);
    }

    public function createAuthenticatedToken(PassportInterface $passport, string $firewallName): TokenInterface
    {
        $tokenAttributes = null;
        // For Symfony 5.1
        if (!method_exists($passport, 'setAttribute')) {
            $tokenAttributes = $this->getCredentials()->getTokenAttributes();
        }

        if (!$passport instanceof UserPassportInterface) {
            throw new LogicException('Passport does not contain a user');
        }

        return new Auth0Token($passport, $firewallName, $tokenAttributes);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->eventDispatcher->dispatch($connectSuccessEvent = new ConnectSuccessEvent($token, $request, $firewallName));
        if ($response = $connectSuccessEvent->getResponse()) {
            return $response;
        }

        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->eventDispatcher->dispatch($connectFailureEvent = new ConnectFailureEvent($request, $exception));
        if ($response = $connectFailureEvent->getResponse()) {
            return $response;
        }

        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

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

    private function getCredentials(): OAuthCredentialsInterface
    {
        if ($this->credentials === null) {
            throw new AuthenticationException('User is not authenticated');
        }

        return $this->credentials;
    }

    private function setCredentials(OAuthCredentialsInterface $credentials): void
    {
        $this->credentials = $credentials;
    }
}
