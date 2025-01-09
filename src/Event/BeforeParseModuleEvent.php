<?php

namespace HeimrichHannot\LoginRegistrationBundle\Event;

use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeParseModuleEvent extends Event
{
    public function __construct(
        public readonly Template $template,
        public readonly ModuleModel $model,
        public readonly Request $request,
        public readonly ?\Exception $exception = null,
    ) {
    }
}
