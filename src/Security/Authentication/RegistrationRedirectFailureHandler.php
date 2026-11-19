<?php

namespace HeimrichHannot\LoginRegistrationBundle\Security\Authentication;

use Contao\CoreBundle\Exception\ResponseException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final class RegistrationRedirectFailureHandler implements AuthenticationFailureHandlerInterface
{
    public const REGISTRATION_REDIRECT = '_login_registration_redirect';

    public function __construct(private readonly AuthenticationFailureHandlerInterface $inner)
    {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $redirect = $request->attributes->get(self::REGISTRATION_REDIRECT);

        // Symfony's legacy user provider wraps Contao's redirect as an authentication failure.
        if ($exception instanceof AuthenticationServiceException
            && $redirect instanceof ResponseException
            && $exception->getPrevious() === $redirect
        ) {
            return $redirect->getResponse();
        }

        return $this->inner->onAuthenticationFailure($request, $exception);
    }
}
