<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace HeimrichHannot\LoginRegistrationBundle\Proxy;

use Contao\Input;
use Contao\ModuleModel;
use Contao\ModuleRegistration;
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

    public static function loadDataContainer($strTable, $blnNoCache = false)
    {
    }


}