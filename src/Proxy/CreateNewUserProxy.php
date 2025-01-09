<?php

namespace HeimrichHannot\LoginRegistrationBundle\Proxy;

use Contao\Module;

class CreateNewUserProxy
{
    private ?int $userId = null;

    public function __invoke(int $userId, array $userData, Module $module): void
    {
        $this->userId = $userId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }
}