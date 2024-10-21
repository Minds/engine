<?php
/**
 * Created by Marcelo.
 * Date: 03/08/2017.
 */

namespace Minds\Core\Wire;

use Minds\Common\Urn;
use Minds\Core;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Session;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;
use Minds\Entities\User;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;

class Events
{
    public function register()
    {
        // Urn resolve
        Dispatcher::register('urn:resolve', 'all', function (Core\Events\Event $event) {
            $urn = $event->getParameters()['urn'];

            if ($urn->getNid() !== 'wire') {
                return;
            }

            /** @var Manager */
            $manager = Di::_()->get('Wire\Manager');
            $event->setResponse($manager->getByUrn((string) $urn->getUrn()));
        });

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

        Dispatcher::register('webhook', 'stripe', function (Core\Events\Event $event) {
            $params = $event->getParameters();
            $webhook = $params['event'];

            $service = new WireWebhookService(
                Di::_()->get(SubscriptionsService::class),
                Di::_()->get(EntitiesBuilder::class),
                new Save(),
                Di::_()->get(Config::class),
                Di::_()->get('Security\ACL'),
                Di::_()->get(StripeApiKeyConfig::class),
                Di::_()->get(GiftCardsManager::class)
            );
            $service->onWebhookEvent($webhook);
        });
    }
}
