<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use HeimrichHannot\LoginRegistrationBundle\Controller\FrontendModule\LoginRegistrationModuleController;

$dca = &$GLOBALS['TL_DCA']['tl_module'];

PaletteManipulator::create()
    ->addField('allowDirectRegistration', 'config_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('login', 'tl_module');

$dca['palettes']['__selector__'][] = 'allowDirectRegistration';
$dca['palettes'][LoginRegistrationModuleController::TYPE] = '{title_legend},name,headline,type;{config_legend},autologin;'
    .'{registration_legend},reg_groups,reg_allowLogin,reg_assignDir,reg_activate;{redirect_legend},jumpTo,redirectBack;'
    .'{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$dca['subpalettes']['allowDirectRegistration'] = 'reg_groups,reg_allowLogin,reg_assignDir,reg_activate';

$dca['fields']['allowDirectRegistration'] = [
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'clr',
        'submitOnChange' => true,
    ],
    'sql'       => "char(1) NOT NULL default ''",
];