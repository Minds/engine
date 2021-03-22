<?php
/**
 * Minds TOTP Provider.
 */

namespace Minds\Core\Security\TOTP;

use Minds\Core\Di\Provider as DiProvider;

/**
 * TOTP Provider
 * @package Minds\Core\Security\TOTP
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Security\TOTP\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Security\TOTP\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
