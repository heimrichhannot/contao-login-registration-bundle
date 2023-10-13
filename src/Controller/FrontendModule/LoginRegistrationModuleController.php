<?php

namespace HeimrichHannot\LoginRegistrationBundle\Controller\FrontendModule;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleLogin;
use Contao\ModuleModel;
use HeimrichHannot\LoginRegistrationBundle\Proxy\RegistrationProxy;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsFrontendModule(LoginRegistrationModuleController::TYPE, category: 'user', template: 'mod_login')]
class LoginRegistrationModuleController extends ModuleLogin
{
    public const TYPE = 'login_registration';

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
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

        parent::compile();
    }


}