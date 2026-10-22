<?php

namespace HeimrichHannot\LoginRegistrationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotLoginRegistrationBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
