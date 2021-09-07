<?php
/**
 * Minds EmailDigests Notifications Provider.
 */

namespace Minds\Core\Notifications\EmailDigests;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Notifications Provider
 * @package Minds\Core\Notifications\EmailDigests
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Notifications\EmailDigests\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
    }
}
