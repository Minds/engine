<?php

namespace Minds\Core\Notifications\PostSubscriptions;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Post subscriptions Provider
 * @package Minds\Core\Notifications\PostSubscriptions
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Notifications\PostSubscriptions\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\PostSubscriptions\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
