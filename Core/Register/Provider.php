<?php
/**
 * Minds Register Provider.
 */

namespace Minds\Core\Register;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Register Provider
 * @package Minds\Core\Register
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Register\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
