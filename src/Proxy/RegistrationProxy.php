<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace HeimrichHannot\LoginRegistrationBundle\Proxy;

use Contao\Input;
use Contao\MemberModel;
use Contao\ModuleModel;
use Contao\ModuleRegistration;
use Contao\Validator;
use HeimrichHannot\LoginRegistrationBundle\Event\PrepareNewMemberDataEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RegistrationProxy extends ModuleRegistration
{
    public const LAST_REGISTRATION = '_security.huh_login_registration.last_registration';

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

        if (!isset($event->getMemberData()['email'])) {
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

    public function doSendActivationMail(array $data)
    {
        $this->sendActivationMail($data);
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