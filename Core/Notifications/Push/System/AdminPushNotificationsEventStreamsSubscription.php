<?php
/**
 * This subscription will deliver push notifications from notifications
 */
namespace Minds\Core\Notifications\Push\System;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications\Push\UndeliverableException;

/**
 *
 */
class AdminPushNotificationsEventStreamsSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?Manager $manager = null,
        private ?Logger $logger = null
    ) {
        $this->manager ??= new Manager();
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'system-push-notifications';
    }

    /**
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface
    {
        return new ActionEventsTopic();
    }

    /**
     * @return string
     */
    public function getTopicRegex(): string
    {
        return ActionEvent::ACTION_SYSTEM_PUSH_NOTIFICATION;
    }

    /**
     * Called when there is a new notification
     * NOTE: the topic delays the delivery
     * @param EventInterface $event
     * @return bool
     * @throws UndeliverableException
     * @throws Exception
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        if ($event->getAction() != ActionEvent::ACTION_SYSTEM_PUSH_NOTIFICATION) {
            return false;
        }

        // immediately acknowledge message to avoid broker picking it up again after acknowledgement timeout
        $event->forceAcknowledge();

        return $this->manager->sendRequestNotifications($event->getEntity());
    }
}
