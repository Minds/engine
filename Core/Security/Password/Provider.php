<?php
/**
 * Minds Password Provider.
 */

namespace Minds\Core\Security\Password;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Password Provider
 * @package Minds\Core\Security\Password
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Security\Password\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Security\Password\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
