<?php

namespace Minds\Core\Media\Video\CloudflareStreams;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Media\Video\CloudflareStreams\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('Media\Video\CloudflareStreams\Controllers', function ($di) {
            return new Controllers();
        });
    }
}
