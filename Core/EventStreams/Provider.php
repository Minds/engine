<?php
/**
 * Minds EventStreams Provider.
 */

namespace Minds\Core\EventStreams;

use Minds\Core\Di;
use Minds\Core\EventStreams\Topics\ViewsTopic;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('EventStreams\Topics\ActionEventsTopic', function ($di) {
            return new Topics\ActionEventsTopic();
        }, ['useFactory' => false]);
        $this->di->bind(ViewsTopic::class, function ($di): ViewsTopic {
            return new ViewsTopic();
        }, ['useFactory' => false]);
    }
}
