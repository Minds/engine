<?php

namespace Minds\Core\Media\Proxy;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Media\Proxy\Controller', function ($di) {
            return new Controller();
        });
    }
}
