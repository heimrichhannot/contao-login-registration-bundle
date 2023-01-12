<?php

namespace HeimrichHannot\LoginRegistrationBundle\Proxy;

use Contao\ModuleLogin;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Response;

class LoginProxy extends ModuleLogin
{
    public function __construct()
    {

    }

    public function __invoke(ModuleModel $model, string $section): Response
    {
        parent::__construct($model, $section);

        return new Response($this->generate());
    }
}