<?php

use HeimrichHannot\LoginRegistrationBundle\Controller\FrontendModule\LoginRegistrationModuleController;

$dca = &$GLOBALS['TL_DCA']['tl_module'];

$dca['palettes'][LoginRegistrationModuleController::TYPE] = '{title_legend},name,headline,type;'
    . '{login_legend},autologin,jumpTo,redirectBack;'
    . '{registration_legend},reg_groups,reg_allowLogin,reg_assignDir,reg_activate,reg_activate_jumpTo,reg_jumpTo;'
    . '{template_legend:collapsed},customTpl;{protected_legend:collapsed},protected;{expert_legend:collapsed},guests,cssID';

$dca['fields']['reg_activate_jumpTo'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => [
        'fieldType' => 'radio',
    ],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => [
        'type' => 'hasOne',
        'load' => 'lazy',
    ],
];
