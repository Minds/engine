<?php
declare(strict_types=1);

namespace Minds\Core\Media;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public function onInit()
    {
        (new MediaProvider())->register();
        (new Routes)->register();
    }
}
