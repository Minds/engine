<?php

namespace Minds\Core\Notifications\UpdateMarkers;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Notifications Update Markers Provider
 * @package Minds\Core\Notifications\UpdateMarkers
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Notifications\UpdateMarkers\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\UpdateMarkers\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
