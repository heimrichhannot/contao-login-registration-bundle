<?php

namespace HeimrichHannot\LoginRegistrationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class AdjustUsernameEvent extends Event
{
    public function __construct(
        private string $username
    )
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }


}