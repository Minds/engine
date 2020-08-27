<?php

namespace Minds\Core\Analytics;

use Minds\Core\Analytics\Graphs;
use Minds\Core\Di\Provider;

class AnalyticsProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Analytics\Graphs\Manager', function ($di) {
            return new Graphs\Manager();
        }, ['useFactory' => true]);

        $this->di->bind('Analytics\Graphs\Repository', function ($di) {
            return new Graphs\Repository();
        }, ['useFactory' => true]);

        $this->di->bind('Analytics\Dashboards\Manager', function ($di) {
            return new Dashboards\Manager();
        }, ['useFactory' => true]);

        $this->di->bind('Analytics\Snowplow\Manager', function ($di) {
            return new Snowplow\Manager();
        }, ['useFactory' => true]);
    }
}
