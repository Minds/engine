<?php
/**
 * ProProvider
 * @author edgebal
 */

namespace Minds\Core\Pro;

use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;

class ProProvider extends Provider
{
    /**
     * @throws ImmutableException
     */
    public function register()
    {
        $this->di->bind('Pro\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => true]);

        $this->di->bind('Pro\Domain', function ($di) {
            return new Domain();
        }, ['useFactory' => true]);

        $this->di->bind('Pro\SEO', function ($di) {
            return new SEO();
        }, ['useFactory' => true]);

        $this->di->bind('Pro\Channel\Manager', function ($di) {
            return new Channel\Manager();
        }, ['useFactory' => true]);

        $this->di->bind('Pro\Assets\Manager', function ($di) {
            return new Assets\Manager();
        }, ['useFactory' => true]);
    }
}
