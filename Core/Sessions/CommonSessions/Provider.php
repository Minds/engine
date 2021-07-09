<?php
/**
 * Minds CommonSessions Provider
 */
namespace Minds\Core\Sessions\CommonSessions;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Sessions\CommonSessions\Manager', function ($di) {
            return new Manager;
        }, ['useFactory'=>false]);
        $this->di->bind('Sessions\CommonSessions\Controller', function ($di) {
            return new Controller;
        }, ['useFactory'=>false]);
    }
}
