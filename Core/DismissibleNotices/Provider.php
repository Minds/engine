<?php

namespace Minds\Core\DismissibleNotices;

use Minds\Core\Di;

/**
 * DismissibleNotice Provider
 */
class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('DismissibleNotices\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('DismissibleNotices\Controller', function ($di) {
            return new Controller();
        });
    }
}
