<?php
declare(strict_types=1);

namespace Minds\Core\Strapi;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function onInit()
    {
        (new Provider())->register();
    }
}
