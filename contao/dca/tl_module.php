<?php

use HeimrichHannot\LoginRegistrationBundle\Controller\FrontendModule\LoginRegistrationModuleController;

$dca = &$GLOBALS['TL_DCA']['tl_module'];

$dca['palettes'][LoginRegistrationModuleController::TYPE] = '{title_legend},name,headline,type;{config_legend},autologin;'
    .'{registration_legend},reg_groups,reg_allowLogin,reg_assignDir,reg_activate;{redirect_legend},jumpTo,redirectBack;'
    .'{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';