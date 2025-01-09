<?php

namespace HeimrichHannot\LoginRegistrationBundle\Exception;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class InvalidPasswordException extends UserNotFoundException
{
}
