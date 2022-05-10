<?php
/**
 * Minds TOTP Provider.
 */

namespace Minds\Core\Security\TwoFactor;

use Minds\Core\Di\Provider as DiProvider;

/**
 * TwoFactor Provider
 * @package Minds\Core\Security\TwoFactor
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Security\TwoFactor\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
    }
}
