<?php

namespace HeimrichHannot\LoginRegistrationBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use HeimrichHannot\LoginRegistrationBundle\HeimrichHannotLoginRegistrationBundle;
use HeimrichHannot\MemberBundle\HeimrichHannotContaoMemberBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class Plugin implements BundlePluginInterface, ConfigPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(HeimrichHannotLoginRegistrationBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    /*
                     * @phpstan-ignore class.notFound
                     */
                    HeimrichHannotContaoMemberBundle::class,
                ]),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load('@HeimrichHannotLoginRegistrationBundle/config/services.yaml');
    }
}
