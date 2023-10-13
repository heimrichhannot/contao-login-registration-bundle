<?php

namespace HeimrichHannot\LoginRegistrationBundle\EventListener;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;

class CheckActivationListener
{
    #[AsHook('getContentElement')]
    public function onGetContentElement(ContentModel $contentModel, string $buffer, $element): string
    {
        return $buffer;
    }
}