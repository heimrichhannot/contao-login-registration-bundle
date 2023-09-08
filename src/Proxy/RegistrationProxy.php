<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace HeimrichHannot\LoginRegistrationBundle\Proxy;

use Contao\ModuleModel;
use Contao\ModuleRegistration;
use Symfony\Component\HttpFoundation\Response;

class RegistrationProxy extends ModuleRegistration
{

    public function createNewUser($arrData)
    {
        parent::createNewUser($arrData);
    }

}