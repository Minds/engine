<?php
namespace Minds\Core\Media\Audio;

use Minds\Core\Di\Di;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public function onInit()
    {
        (new Provider())->register();
        (Di::_()->get(Events::class))->register();
        (new Routes())->register();
    }
}
