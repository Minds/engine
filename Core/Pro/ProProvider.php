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
    }
}
