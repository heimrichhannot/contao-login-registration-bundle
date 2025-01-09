<?php

namespace HeimrichHannot\LoginRegistrationBundle\Security;

use Contao\User;
use HeimrichHannot\LoginRegistrationBundle\Proxy\RegistrationProxy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RegistrationUtils
{
    public function __construct(
        private readonly RequestStack $requestStack
    )
    {
    }

    public function setLastRegisteredUser(User $member): void
    {
        $request = $this->getRequest();

        $request->getSession()->set(RegistrationProxy::LAST_REGISTRATION, $member);
    }

    public function getLastRegisteredUser(bool $clearSession = true): ?User
    {
        $request = $this->getRequest();
        $user = null;

        if ($request->hasSession() && ($session = $request->getSession())->has(RegistrationProxy::LAST_REGISTRATION)) {
            $user = $session->get(RegistrationProxy::LAST_REGISTRATION);

            if ($clearSession) {
                $session->remove(RegistrationProxy::LAST_REGISTRATION);
            }
        }

        return $user;
    }

    private function getRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \LogicException('Request should exist so it can be processed for error.');
        }

        return $request;
    }
}