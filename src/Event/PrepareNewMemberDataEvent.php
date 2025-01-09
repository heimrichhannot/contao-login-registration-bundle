<?php

namespace HeimrichHannot\LoginRegistrationBundle\Event;

use Contao\ModuleModel;
use Symfony\Contracts\EventDispatcher\Event;

class PrepareNewMemberDataEvent extends Event
{
    public function __construct(
        private array $memberData,
        private readonly ModuleModel $moduleModel,
    ) {
    }

    public function getMemberData(): array
    {
        return $this->memberData;
    }

    public function getModuleModel(): ModuleModel
    {
        return $this->moduleModel;
    }

    public function setMemberData(array $memberData): void
    {
        $this->memberData = $memberData;
    }

    public function addMemberData(string $key, string|int $value): void
    {
        $this->memberData[$key] = $value;
    }
}
