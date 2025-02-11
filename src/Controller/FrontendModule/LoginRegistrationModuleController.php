<?php

namespace HeimrichHannot\LoginRegistrationBundle\Controller\FrontendModule;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\MemberModel;
use Contao\ModuleLogin;
use Contao\ModuleModel;
use Contao\PageModel;
use HeimrichHannot\LoginRegistrationBundle\Event\BeforeParseModuleEvent;
use HeimrichHannot\LoginRegistrationBundle\Exception\InvalidPasswordException;
use HeimrichHannot\LoginRegistrationBundle\Exception\InvalidRegistrationConfigurationException;
use HeimrichHannot\LoginRegistrationBundle\Exception\InvalidRegistrationException;
use HeimrichHannot\LoginRegistrationBundle\Registration\RegistrationProxy;
use HeimrichHannot\LoginRegistrationBundle\Security\RegistrationUtils;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(LoginRegistrationModuleController::TYPE, category: 'user', template: 'mod_login')]
class LoginRegistrationModuleController extends ModuleLogin
{
    public const TYPE = 'login_registration';

    /**
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AuthenticationUtils $authUtils,
        private readonly RequestStack $requestStack,
        private readonly RegistrationUtils $registrationUtils,
        private readonly TranslatorInterface $translator,
    ) {
    }

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
        $exception = null;
        if ($request && $request->hasSession() && ($request->hasPreviousSession() || $request->getSession()->isStarted())) {
            $exception = $this->authUtils->getLastAuthenticationError(false);
        }

        $this->checkRegistration($exception, $registration);

        parent::compile();

        if ($exception) {
            if ($exception instanceof InvalidPasswordException) {
                $this->Template->message = $this->translator->trans('huh_login_registration.errors.invalid_password');
            } elseif ($exception instanceof InvalidRegistrationConfigurationException) {
                $this->Template->message = $this->translator->trans('huh_login_registration.errors.invalid_registration_configuration');
            } elseif ($exception instanceof InvalidRegistrationException) {
                $this->Template->message = $this->translator->trans(
                    'huh_login_registration.errors.invalid_registration',
                    [
                        '%error%' => $exception->getMessage(),
                    ]
                );
            }
        }

        $this->eventDispatcher->dispatch(new BeforeParseModuleEvent(
            $this->Template,
            $this->objModel,
            $request,
            $exception,
        ));
    }

    private function checkRegistration(?AuthenticationException $exception, RegistrationProxy $registration): void
    {
        if (!$exception instanceof DisabledException) {
            return;
        }

        $lastUser = $this->registrationUtils->getLastRegisteredUser(true);

        if (!$lastUser || ($lastUser->getUserIdentifier() !== $exception->getUser()->getUserIdentifier())) {
            return;
        }

        $memberModel = MemberModel::findByUsername($lastUser->getUserIdentifier());
        if (!$memberModel->disable) {
            return;
        }

        /*
         * Check whether there is a jumpTo page
         *
         * @phpstan-ignore instanceof.alwaysFalse
         */
        if (($objJumpTo = $this->objModel->getRelated('reg_activate_jumpTo')) instanceof PageModel) {
            $this->jumpToOrReload($objJumpTo->row());
        }

        $this->reload();
    }
}
