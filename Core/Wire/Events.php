<?php
/**
 * Created by Marcelo.
 * Date: 03/08/2017.
 */

namespace Minds\Core\Wire;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Events\Dispatcher;
use Minds\Entities\User;

class Events
{
    public function register()
    {
        // Recurring subscriptions

        Dispatcher::register('subscriptions:process', 'wire', function (Core\Events\Event $event) {
            $params = $event->getParameters();
            /** @var Core\Payments\Subscriptions\Subscription $subscription */
            $subscription = $params['subscription'];

            $manager = Di::_()->get('Wire\Subscriptions\Manager');
            $result = $manager->onRecurring($subscription);

            return $event->setResponse($result);
        });

        // Wire emails

        Dispatcher::register('wire:email', 'wire', function (Core\Events\Event $event) {
            $params = $event->getParameters();
            $wire = $params['wire'];
            $campaign = (new Core\Email\V2\Campaigns\Recurring\WireReceived\WireReceived())
                ->setUser($wire->getReceiver())
                ->setWire($wire)
                ->send();

            return $event->setResponse(true);
        });

        // Wire emails
        Dispatcher::register('wire-receipt:email', 'wire', function (Core\Events\Event $event) {
            $params = $event->getParameters();
            $wire = $params['wire'];
            $campaign = (new Core\Email\V2\Campaigns\Recurring\WireSent\WireSent())
                ->setUser($wire->getSender())
                ->setWire($wire)
                ->send();

            return $event->setResponse(true);
        });

        Dispatcher::register('acl:write', 'all', function (Core\Events\Event $event) {
            $params = $event->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];

            if (!$entity instanceof Wire) {
                return;
            }

            $event->setResponse($entity->getSender()->guid === $user->guid);
        });
    }
}
