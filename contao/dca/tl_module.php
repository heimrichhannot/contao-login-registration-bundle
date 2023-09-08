<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$dca = &$GLOBALS['TL_DCA']['tl_module'];

PaletteManipulator::create()
    ->addField('allowDirectRegistration', 'config_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('login', 'tl_module');

$dca['palettes']['__selector__'][] = 'allowDirectRegistration';
$dca['subpalettes']['allowDirectRegistration'] = 'reg_groups,reg_allowLogin,reg_assignDir,reg_activate';

$dca['fields']['allowDirectRegistration'] = [
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'clr',
        'submitOnChange' => true,
    ],
    'sql'       => "char(1) NOT NULL default ''",
];