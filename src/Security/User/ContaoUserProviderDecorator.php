<?php

namespace HeimrichHannot\LoginRegistrationBundle\Security\User;

use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\ModuleModel;
use Contao\User;
use HeimrichHannot\LoginRegistrationBundle\Proxy\RegistrationProxy;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ContaoUserProviderDecorator implements UserProviderInterface, PasswordUpgraderInterface
{

    public function __construct(
        private ContaoUserProvider $contaoUserProvider,
        private RequestStack $requestStack,
    )
    {
    }

    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->contaoUserProvider, $name)) {
            call_user_func([$this->contaoUserProvider, $name], $arguments);
        }

        $class = self::class;
        throw new \BadMethodCallException("Method $class::$name does not exist.");
    }

    public function refreshUser(UserInterface $user): UserInterface|User
    {
        return $this->contaoUserProvider->refreshUser($user);
    }

    public function supportsClass(string $class): bool
    {
        return $this->contaoUserProvider->supportsClass($class);
    }

    public function loadUserByUsername(string $username): UserInterface|User
    {
        try {
            $user = $this->contaoUserProvider->loadUserByUsername($username);
        } catch (UserNotFoundException|UsernameNotFoundException $exception) {
            return $this->applyDirectRegistration($username);
        }

        return $user;
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        try {
            $user = $this->contaoUserProvider->loadUserByIdentifier($identifier);
        } catch (UserNotFoundException|UsernameNotFoundException $exception) {
            return $this->applyDirectRegistration($identifier);
        }
        return $user;
    }

    private function applyDirectRegistration(string $identifier): User
    {
        $userNotFoundException = new UserNotFoundException(sprintf('Could not find user "%s"', $identifier));

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw $userNotFoundException;
        }

        $moduleId = str_replace('tl_login_', '', $request->request->get('FORM_SUBMIT', ''));
        if (empty($moduleId) || !is_numeric($moduleId)) {
            throw $userNotFoundException;
        }

        $moduleModel = ModuleModel::findByPk((int)$moduleId);
        if (!$moduleModel || !$moduleModel->allowDirectRegistration) {
            throw $userNotFoundException;
        }

        $registrationModuleModel = new ModuleModel();
        $registrationModuleModel->reg_allowLogin = $moduleModel->reg_allowLogin;
        $registrationModuleModel->reg_groups = $moduleModel->reg_groups;
        $registrationModuleModel->reg_assignDir = $moduleModel->reg_assignDir;
        $registrationModuleModel->reg_homeDir = $moduleModel->reg_homeDir;
        $registrationModule = new RegistrationProxy($registrationModuleModel);


        throw $userNotFoundException;
    }
}