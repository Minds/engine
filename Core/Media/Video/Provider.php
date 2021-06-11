<?php

namespace Minds\Core\Media\Video;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Media\Video\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('Media\Video\Controller', function ($di) {
            return new Controller();
        });
    }
}
