<?php

namespace HeimrichHannot\LoginRegistrationBundle\Security\User;

use Contao\Controller;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\FormPassword;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\ModuleModel;
use Contao\User;
use HeimrichHannot\LoginRegistrationBundle\Controller\FrontendModule\LoginRegistrationModuleController;
use HeimrichHannot\LoginRegistrationBundle\Event\AdjustUsernameEvent;
use HeimrichHannot\LoginRegistrationBundle\EventListener\Contao\ParseWidgetListener;
use HeimrichHannot\LoginRegistrationBundle\Exception\InvalidPasswordException;
use HeimrichHannot\LoginRegistrationBundle\Exception\InvalidRegistrationConfigurationException;
use HeimrichHannot\LoginRegistrationBundle\Registration\RegistrationProxy;
use HeimrichHannot\LoginRegistrationBundle\Security\RegistrationUtils;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ContaoUserProviderDecorator implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly ContaoUserProvider $contaoUserProvider,
        private readonly RequestStack $requestStack,
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RegistrationUtils $registrationUtils,
        private readonly ParseWidgetListener $parseWidgetListener,
    ) {
    }

    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->contaoUserProvider, $name)) {
            call_user_func([$this->contaoUserProvider, $name], ...$arguments);
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
        $event = $this->eventDispatcher->dispatch(new AdjustUsernameEvent($username));

        try {
            $user = $this->contaoUserProvider->loadUserByUsername($event->getUsername());
        } catch (UserNotFoundException|UsernameNotFoundException) {
            return $this->applyDirectRegistration($event->getUsername());
        }

        return $user;
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $event = $this->eventDispatcher->dispatch(new AdjustUsernameEvent($identifier));

        try {
            $user = $this->contaoUserProvider->loadUserByIdentifier($event->getUsername());
        } catch (UserNotFoundException|UsernameNotFoundException) {
            return $this->applyDirectRegistration($event->getUsername());
        }

        return $user;
    }

    private function applyDirectRegistration(string $identifier): User
    {
        $this->framework->initialize();

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new InvalidRegistrationConfigurationException('No request available.');
        }

        $moduleId = str_replace('tl_login_', '', $request->request->get('FORM_SUBMIT', ''));
        if (empty($moduleId) || !is_numeric($moduleId)) {
            throw new InvalidRegistrationConfigurationException('No module id found.');
        }

        $moduleModel = ModuleModel::findByPk((int) $moduleId);
        if (!$moduleModel || LoginRegistrationModuleController::TYPE !== $moduleModel->type) {
            throw new InvalidRegistrationConfigurationException('No valid module found.');
        }

        if (!isset($GLOBALS['objPage'])) {
            $GLOBALS['objPage'] = $request->attributes->get('pageModel');
        }

        if (!defined('FE_USER_LOGGED_IN')) {
            define('FE_USER_LOGGED_IN', false);
        }

        Input::setPost('FORM_SUBMIT', 'tl_registration_' . $moduleModel->id);
        Input::setPost('username', $identifier);

        Controller::loadLanguageFile('default');
        Controller::loadDataContainer('tl_member');

        $registrationModule = RegistrationProxy::createInstance(
            $moduleModel->row(),
            $this->eventDispatcher
        );
        $registrationModule->Template = new FrontendTemplate();

        try {
            $this->parseWidgetListener->clear();
            $registrationModule->runCompile();
        } catch (ResponseException) {
        }

        foreach ($this->parseWidgetListener->getWidgets() as $widget) {
            if ($widget->hasErrors()) {
                if ($widget instanceof FormPassword) {
                    throw new InvalidPasswordException($widget->getErrorAsString());
                }

                throw new UserNotFoundException($widget->getErrorsAsString());
            }
        }

        $user = $this->contaoUserProvider->loadUserByIdentifier($identifier);

        $this->registrationUtils->setLastRegisteredUser($user);

        return $user;
    }
}
