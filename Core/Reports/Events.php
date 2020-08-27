<?php

/**
 */

namespace Minds\Core\Reports;

use Minds\Core;
use Minds\Entities;
use Minds\Helpers;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Analytics\Metrics\Event;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Channels\Delegates\Ban;

class Events
{
    public function register()
    {
        Di::_()->get('EventsDispatcher')->register('ban', 'user', function ($event) {
            $config = Di::_()->get('Config');
            $user = $event->getParameters();

            // Record metric
            $event = new Event();
            $event->setType('action')
                ->setProduct('platform')
                ->setUserGuid((string) Core\Session::getLoggedInUser()->guid)
                ->setEntityGuid((string) $user->getGuid())
                ->setAction("ban")
                ->setBanReason($user->ban_reason)
                ->push();
        });
    }
}
