<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace HeimrichHannot\LoginRegistrationBundle\Proxy;

use Contao\Controller;
use Contao\Input;
use Contao\MemberModel;
use Contao\ModuleModel;
use Contao\ModuleRegistration;
use Contao\System;
use Contao\Validator;
use HeimrichHannot\LoginRegistrationBundle\Event\PrepareNewMemberDataEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RegistrationProxy extends ModuleRegistration
{
    public function __construct(
        private ModuleModel  $moduleModel,
        private EventDispatcherInterface $eventDispatcher,
    )
    {
        parent::__construct($moduleModel);
    }

    public function createNewUser($arrData)
    {
        $arrData['username'] = Input::post('username');
        if (!isset($arrData['email']) && Validator::isEmail($arrData['username'])) {
            $arrData['email'] = $arrData['username'];
        }

        $event = $this->eventDispatcher->dispatch(new PrepareNewMemberDataEvent($arrData, $this->moduleModel));

        if (!isset($arrData['email'])) {
            throw new \Exception('No email address provided for new user!');
        }

        parent::createNewUser($event->getMemberData());
    }

    public function runCompile() {
        parent::compile();
    }

    public function checkActivation(): bool
    {
        $strFormId = 'tl_login_' . $this->id;

        // Remove expired registration (#3709)
        if (Input::post('FORM_SUBMIT') == $strFormId && ($email = Input::post('email')) && ($member = MemberModel::findExpiredRegistrationByEmail($email)))
        {
            $member->delete();
        }

        // Activate account
        if (strncmp(Input::get('token'), 'reg-', 4) === 0)
        {
            $this->activateAcount();
            return true;
        }

        return false;
    }

    public static function createInstance(array $data, EventDispatcherInterface $eventDispatcher): self
    {
        $registrationModuleModel = new ModuleModel();
        $registrationModuleModel->setRow($data);
        $registrationModuleModel->type = 'registration';
        $registrationModuleModel->editable = ['username', 'password'];
        $registrationModuleModel->disableCaptcha = '1';
        return new RegistrationProxy($registrationModuleModel, $eventDispatcher);
    }
}