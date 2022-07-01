<?php

namespace Minds\Core\FeedNotices;

use Minds\Core\Di;

/**
 * Provider for FeedNotices.
 */
class Provider extends Di\Provider
{
    /**
     * Register with provider.
     * @return void
     */
    public function register()
    {
        $this->di->bind('FeedNotices\Manager', function ($di) {
            return new Manager();
        });

        $this->di->bind('FeedNotices\Controller', function ($di) {
            return new Controller();
        });
    }
}
