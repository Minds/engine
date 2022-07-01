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
        $this->di->bind('Notifications\Push\Settings\Manager', function ($di) {
            return new Settings\Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Push\Settings\Controller', function ($di) {
            return new Settings\Controller();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Push\System\Controller', function ($di) {
            return new System\Controller();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Push\System\Manager', function ($di) {
            return new System\Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Push\TopPost\Manager', function ($di) {
            return new TopPost\Manager();
        }, ['useFactory' => false]);
    }
}
