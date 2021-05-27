<?php
/**
 * Minds Push Notifications Provider.
 */

namespace Minds\Core\Notifications\Push;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Notifications Provider
 * @package Minds\Core\Notifications
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Notifications\Push\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Push\DeviceSubscriptions\Controller', function ($di) {
            return new DeviceSubscriptions\Controller();
        }, ['useFactory' => false]);
    }
}
