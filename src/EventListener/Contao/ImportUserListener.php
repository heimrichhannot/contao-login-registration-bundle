<?php

namespace HeimrichHannot\LoginRegistrationBundle\EventListener\Contao;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;

#[AsHook('importUser')]
class ImportUserListener
{
    public function __invoke(string $username, string $password, string $table): bool
    {
        return false;
    }
}