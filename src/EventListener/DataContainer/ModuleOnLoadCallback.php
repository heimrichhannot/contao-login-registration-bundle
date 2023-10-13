<?php

namespace HeimrichHannot\LoginRegistrationBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Contao\ModuleModel;

#[AsCallback(table: 'tl_module', target: 'config.onload')]
class ModuleOnLoadCallback
{
    public function __invoke(DataContainer $dc = null): void
    {
        if (!$dc || 'edit' !== Input::get('act') || !$dc->id) {
            return;
        }

        $module = ModuleModel::findByPk($dc->id);
        if (!$module || 'login' !== $module->type) {
            return;
        }

        unset($GLOBALS['TL_DCA']['tl_module']['subpalettes']['reg_activate']);
        unset($GLOBALS['TL_DCA']['tl_module']['subpalettes']['reg_assignDir']);

        if ($module->reg_activate) {
            PaletteManipulator::create()
                ->addField('reg_text', 'reg_activate')
                ->addField('reg_jumpTo', 'reg_activate')
                ->applyToSubpalette('allowDirectRegistration', 'tl_module');
        }

        if ($module->reg_assignDir) {
            PaletteManipulator::create()
                ->addField('reg_homeDir', 'reg_assignDir')
                ->applyToSubpalette('allowDirectRegistration', 'tl_module');
        }
    }
}