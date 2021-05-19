<?php
/**
 * Minds Notifications Provider.
 */

namespace Minds\Core\Notifications;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Notifications Provider
 * @package Minds\Core\Notifications
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Notifications\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
