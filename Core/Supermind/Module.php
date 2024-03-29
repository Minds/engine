<?php
declare(strict_types=1);

namespace Minds\Core\Supermind;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /** @var array $submodules */
    public $submodules = [
        Settings\Module::class,
    ];

    /**
     * @return void
     */
    public function onInit(): void
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
