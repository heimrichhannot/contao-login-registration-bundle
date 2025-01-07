<?php

namespace HeimrichHannot\LoginRegistrationBundle\Controller\FrontendModule;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleLogin;
use Contao\ModuleModel;
use HeimrichHannot\LoginRegistrationBundle\Event\BeforeParseModuleEvent;
use HeimrichHannot\LoginRegistrationBundle\Proxy\RegistrationProxy;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsFrontendModule(LoginRegistrationModuleController::TYPE, category: 'user', template: 'mod_login')]
class LoginRegistrationModuleController extends ModuleLogin
{
    public const TYPE = 'login_registration';

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AuthenticationUtils      $authUtils,
        private readonly RequestStack             $requestStack,
    ){}

    public function __invoke(ModuleModel $model, string $section): Response
    {
        parent::__construct($model, $section);

        return new Response($this->generate());
    }

    protected function compile()
    {
        $registration = RegistrationProxy::createInstance($this->getModel()->row(), $this->eventDispatcher);
        if ($registration->checkActivation()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        // Only call the authentication utils if there is an active session to prevent starting an empty session
        if ($request && $request->hasSession() && ($request->hasPreviousSession() || $request->getSession()->isStarted()))
        {
            $exception = $this->authUtils->getLastAuthenticationError(false);
        }

        parent::compile();

        $this->eventDispatcher->dispatch(new BeforeParseModuleEvent(
            $this->Template,
            $this->getModel(),
            $request,
            $exception,
        ));
    }


}