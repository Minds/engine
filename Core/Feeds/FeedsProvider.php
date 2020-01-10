<?php

namespace Minds\Core\Feeds;

use Minds\Core\Di\Provider;

class FeedsProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Feeds\Elastic\Manager', function ($di) {
            return new Elastic\Manager();
        });

        $this->di->bind('Feeds\Activity\Manager', function ($di) {
            return new Activity\Manager();
        });

        $this->di->bind('Feeds\Firehose\Manager', function ($di) {
            return new Firehose\Manager();
        });
    }
}
