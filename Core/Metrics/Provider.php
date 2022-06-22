<?php
namespace Minds\Core\Metrics;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Metrics\Controller', function ($di) {
            return new Controller();
        });
    }
}
