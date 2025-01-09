<?php

namespace HeimrichHannot\LoginRegistrationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotLoginRegistrationBundle extends Bundle
{
    public function getPath()
    {
        return \dirname(__DIR__);
    }
}
