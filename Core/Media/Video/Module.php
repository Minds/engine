<?php
namespace Minds\Core\Media\Video;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * OnInit
     */
    public function onInit()
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
