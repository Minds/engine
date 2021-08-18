<?php

namespace Minds\Core\OEmbed;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('OEmbed\Controller', function ($di) {
            return new Controller();
        });
    }
}
