<?php
namespace Minds\Core\Boost\V3\Ranking;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public function onInit(): void
    {
        $provider = new Provider();
        $provider->register();
    }
}
