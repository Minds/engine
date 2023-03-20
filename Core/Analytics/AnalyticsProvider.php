<?php

namespace Minds\Core\Analytics;

use Minds\Common\PseudonymousIdentifier;
use Minds\Core\Analytics\Graphs;
use Minds\Core\Analytics\Clicks\Manager as ClicksManager;
use Minds\Core\Analytics\Clicks\Delegates\ActionEventsDelegate as ClickActionEventsDelegate;
use Minds\Core\Analytics\Clicks\Delegates\SnowplowDelegate;
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

        $this->di->bind('Analytics\Views\Manager', function ($di) {
            return new Views\Manager();
        }, ['useFactor' => true]);

        $this->di->bind('Analytics\Snowplow\Manager', function ($di) {
            return new Snowplow\Manager(null, null, new PseudonymousIdentifier());
        }, ['useFactory' => true]);

        $this->di->bind(Controller::class, function ($di) {
            return new Controller();
        }, ['useFactory' => true]);

        $this->di->bind(ClicksManager::class, function ($di) {
            return new ClicksManager();
        }, ['useFactory' => true]);

        $this->di->bind(ClickActionEventsDelegate::class, function ($di) {
            return new ClickActionEventsDelegate();
        }, ['useFactory' => true]);

        $this->di->bind(SnowplowDelegate::class, function ($di) {
            return new SnowplowDelegate();
        });
    }
}
