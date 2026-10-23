<?php

declare(strict_types=1);

require getenv('TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\FrontendUser;
use Contao\User;
use HeimrichHannot\LoginRegistrationBundle\Controller\FrontendModule\LoginRegistrationModuleController;
use HeimrichHannot\LoginRegistrationBundle\Event\AdjustUsernameEvent;
use HeimrichHannot\LoginRegistrationBundle\EventListener\Contao\ParseWidgetListener;
use HeimrichHannot\LoginRegistrationBundle\Exception\InvalidRegistrationConfigurationException;
use HeimrichHannot\LoginRegistrationBundle\HeimrichHannotLoginRegistrationBundle;
use HeimrichHannot\LoginRegistrationBundle\Registration\RegistrationProxy;
use HeimrichHannot\LoginRegistrationBundle\Security\RegistrationUtils;
use HeimrichHannot\LoginRegistrationBundle\Security\User\ContaoUserProviderDecorator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Yaml\Yaml;

// No kernel boot, database connection, registration, HTTP request or mail send.
final class CompatibilityFramework extends ContaoFramework
{
    public int $initializations = 0;

    public function __construct()
    {
    }

    public function initialize(): void
    {
        ++$this->initializations;
    }
}

final class CompatibilityProvider extends ContaoUserProvider
{
    public User $user;
    public array $calls = [];
    public bool $missing = false;

    public function __construct()
    {
        $this->user = (new ReflectionClass(FrontendUser::class))->newInstanceWithoutConstructor();
    }

    public function supportsClass(string $class): bool
    {
        return FrontendUser::class === $class;
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $this->calls[] = ['load', $identifier];
        if ($this->missing) {
            throw new UserNotFoundException('Synthetic missing user');
        }

        return $this->user;
    }

    public function loadUserById(int $id): User
    {
        $this->calls[] = ['load-id', $id];

        return $this->user;
    }

    public function refreshUser(UserInterface $user): User
    {
        $this->calls[] = ['refresh', $user];

        return $this->user;
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $this->calls[] = ['upgrade', $user, $newHashedPassword];
    }

    public function echoArguments(string $first, int $second): array
    {
        return [$first, $second];
    }

    public function nullResult(): mixed
    {
        return null;
    }

    private function privateOperation(): void
    {
    }
}

$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    ++$assertions;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$throws = static function (callable $call, string $expected, string $message) use ($assert): void {
    try {
        $call();
    } catch (Throwable $exception) {
        $assert($exception instanceof $expected, $message . ' (got ' . $exception::class . ')');

        return;
    }
    $assert(false, $message . ' (no exception)');
};

$framework = new CompatibilityFramework();
$stack = new RequestStack();
$events = new EventDispatcher();
$utils = new RegistrationUtils($stack);
$widgets = new ParseWidgetListener();
$inner = new CompatibilityProvider();
$provider = new ContaoUserProviderDecorator($inner, $stack, $framework, $events, $utils, $widgets);

$assert($provider instanceof PasswordUpgraderInterface, 'Provider must implement the Symfony password upgrader contract');
$assert($provider instanceof ContaoUserProvider, 'The decorator must satisfy Contao concrete provider consumers');
$provider->upgradePassword($inner->user, 'synthetic-hash');
$assert(end($inner->calls) === ['upgrade', $inner->user, 'synthetic-hash'], 'Password upgrade must delegate the exact user and hash once');
$assert(1 === count($inner->calls), 'Password upgrade must have exactly one side effect');
$assert($provider->echoArguments('one', 2) === ['one', 2], 'Magic delegation must preserve arguments and returned values');
$assert(null === $provider->nullResult(), 'A null result must not fall through to an exception');
$assert($provider->loadUserById(42) === $inner->user, 'Core provider extension loadUserById must remain available');
$assert(end($inner->calls) === ['load-id', 42], 'Core extension arguments must be unchanged');
$throws(fn () => $provider->unknownOperation(), BadMethodCallException::class, 'Unknown methods must report BadMethodCallException');
$throws(fn () => $provider->privateOperation(), BadMethodCallException::class, 'Private inner methods must not be callable');
$assert($provider->supportsClass(FrontendUser::class), 'Frontend user support must delegate');
$assert(!$provider->supportsClass(stdClass::class), 'Unsupported classes must remain unsupported');
$assert($provider->refreshUser($inner->user) === $inner->user, 'Refresh must preserve the returned user');

$events->addListener(AdjustUsernameEvent::class, static function (AdjustUsernameEvent $event): void {
    $event->setUsername(trim($event->getUsername()));
});
$assert($provider->loadUserByIdentifier(' existing ') === $inner->user, 'Existing users must retain their normal login path');
$assert(end($inner->calls) === ['load', 'existing'], 'The username event must run before lookup');
$assert($provider->loadUserByUsername(' legacy ') === $inner->user, 'The deprecated username alias must delegate through identifier lookup');
$assert(end($inner->calls) === ['load', 'legacy'], 'The alias must not call removed inner loadUserByUsername');
$assert(0 === $framework->initializations, 'Normal login/delegation must not start direct registration');

$inner->missing = true;
$throws(fn () => $provider->loadUserByIdentifier('absent'), InvalidRegistrationConfigurationException::class, 'Missing request must reject direct registration safely');
$request = Request::create('/ordinary-login', 'POST', [
    'FORM_SUBMIT' => 'unrelated_form',
]);
$stack->push($request);
$throws(fn () => $provider->loadUserByIdentifier('absent'), InvalidRegistrationConfigurationException::class, 'Unrelated forms must not trigger registration');
$assert('unrelated_form' === $request->request->get('FORM_SUBMIT'), 'Rejected registration must not mutate the submitted form');
$assert(!defined('FE_USER_LOGGED_IN'), 'The provider must not create the removed global login constant');

// Compile the actual bundle decoration contract with in-memory collaborators.
$serviceFile = dirname(__DIR__) . '/config/services.yaml';
$definition = Yaml::parseFile($serviceFile)['services'][ContaoUserProviderDecorator::class];
$assert('contao.security.frontend_user_provider' === $definition['decorates'], 'Only the frontend provider may be decorated');
$container = new ContainerBuilder();
$container->register('contao.security.frontend_user_provider', CompatibilityProvider::class)->setPublic(true);
$container->register('contao.security.backend_user_provider', CompatibilityProvider::class)->setPublic(true);
foreach ([
    'stack' => $stack,
    'framework' => $framework,
    'events' => $events,
    'utils' => $utils,
    'widgets' => $widgets,
] as $id => $service) {
    $container->register($id, $service::class)->setSynthetic(true)->setPublic(true);
    $container->set($id, $service);
}
$container->register(ContaoUserProviderDecorator::class, ContaoUserProviderDecorator::class)
    ->setDecoratedService($definition['decorates'])
    ->setArguments([
        new Reference(ContaoUserProviderDecorator::class . '.inner'),
        new Reference('stack'), new Reference('framework'), new Reference('events'),
        new Reference('utils'), new Reference('widgets'),
    ])->setPublic(true);
$container->compile();
$assert($container->get('contao.security.frontend_user_provider') instanceof ContaoUserProviderDecorator, 'Compiled frontend provider must use the decorator');
$assert($container->get('contao.security.backend_user_provider') instanceof CompatibilityProvider, 'Backend provider must remain independent');
$assert($container->get('contao.security.frontend_user_provider')->supportsClass(FrontendUser::class), 'Compiled provider must be callable');

foreach ([LoginRegistrationModuleController::class, RegistrationProxy::class, HeimrichHannotLoginRegistrationBundle::class] as $class) {
    $assert(class_exists($class), 'Symfony 7 must load ' . $class . ' without a signature fatal');
}
$assert(is_string((new HeimrichHannotLoginRegistrationBundle())->getPath()), 'Bundle path must satisfy Symfony 7 return type');
// Contao 5.3 lacks loadUserById(): reject an unavailable optional method explicitly.
if (!method_exists(ContaoUserProvider::class, 'loadUserById')) {
    $legacyProvider = new ContaoUserProviderDecorator(
        new ContaoUserProvider($framework, FrontendUser::class),
        $stack, $framework, $events, $utils, $widgets,
    );
    $initializations = $framework->initializations;
    $throws(fn () => $legacyProvider->loadUserById(42), BadMethodCallException::class, 'Unavailable optional lookup must fail without calling a missing core method');
    $assert($framework->initializations === $initializations, 'Unsupported optional lookup must not access the database');
}

printf("PASS LoginRegistration compatibility: %d assertions (mock providers, no database/API/mail)\n", $assertions);
