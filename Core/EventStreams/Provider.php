<?php
/**
 * Minds EventStreams Provider.
 */

namespace Minds\Core\EventStreams;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('EventStreams\Topics\ActionEventsTopic', function ($di) {
            return new Topics\ActionEventsTopic();
        }, ['useFactory' => false]);
    }
}
