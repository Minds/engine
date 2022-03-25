<?php
namespace Minds\Core\DismissibleNotices;

use Minds\Interfaces\ModuleInterface;

/**
 * Dismissible notices module.
 */
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
