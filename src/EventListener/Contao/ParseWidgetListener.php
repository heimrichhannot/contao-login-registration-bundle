<?php

namespace HeimrichHannot\LoginRegistrationBundle\EventListener\Contao;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Widget;

#[AsHook('parseWidget')]
class ParseWidgetListener
{
    /**
     * @var array<Widget>
     */
    private array $widgets = [];

    public function __invoke(string $buffer, Widget $widget): string
    {
        $this->widgets[] = $widget;

        return $buffer;
    }

    public function clear(): void
    {
        $this->widgets = [];
    }

    public function getWidgets(): array
    {
        return $this->widgets;
    }
}
