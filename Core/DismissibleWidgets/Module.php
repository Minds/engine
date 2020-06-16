<?php
namespace Minds\Core\DismissibleWidgets;

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
