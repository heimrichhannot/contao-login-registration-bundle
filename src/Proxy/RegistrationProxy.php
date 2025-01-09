<?php

/** @noinspection PhpMissingParentConstructorInspection */

namespace HeimrichHannot\LoginRegistrationBundle\Proxy;

use Contao\Input;
use Contao\ModuleModel;
use Contao\ModuleRegistration;
use Contao\Validator;
use HeimrichHannot\LoginRegistrationBundle\Event\PrepareNewMemberDataEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RegistrationProxy extends ModuleRegistration
{
    public const LAST_REGISTRATION = '_security.huh_login_registration.last_registration';

    public function __construct(
        private readonly ModuleModel $moduleModel,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($moduleModel);
    }

    public function createNewUser($arrData): void
    {
        $arrData['username'] = Input::post('username');
        if (!isset($arrData['email']) && Validator::isEmail($arrData['username'])) {
            $arrData['email'] = $arrData['username'];
        }

        $event = $this->eventDispatcher->dispatch(new PrepareNewMemberDataEvent($arrData, $this->moduleModel));

        if (!isset($event->getMemberData()['email'])) {
            throw new \Exception('No email address provided for new user!');
        }

        parent::createNewUser($event->getMemberData());
    }

    public function runCompile(): void
    {
        parent::compile();
    }

    public function checkActivation(): bool
    {
        // Activate account
        if (str_starts_with(Input::get('token'), 'reg-')) {
            $this->activateAcount();

            return true;
        }

        return false;
    }

    public static function createInstance(array $data, EventDispatcherInterface $eventDispatcher): self
    {
        $data['jumpTo'] = $data['reg_activate_jumpTo'];

        $registrationModuleModel = new ModuleModel();
        $registrationModuleModel->setRow($data);
        $registrationModuleModel->type = 'registration';
        $registrationModuleModel->editable = ['username', 'password'];
        $registrationModuleModel->disableCaptcha = '1';

        return new RegistrationProxy($registrationModuleModel, $eventDispatcher);
    }
}
