<?php
/**
 * CountersProvider
 * @author edgebal
 */

namespace Minds\Core\Counters;

use Minds\Core\Di\Provider;

class CountersProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Counters', function ($di) {
            return new Manager();
        }, ['useFactory' => true]);
    }
}
