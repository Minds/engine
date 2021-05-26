<?php
/**
 * This subscription will deliver push notifications from notifications
 */
namespace Minds\Core\Notifications\Push;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\NotificationEvent;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\NotificationsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;

class PushNotificationsEventStreamsSubscription implements SubscriptionInterface
{
    /** @var Manager */
    protected $manager;

    /** @var Logger */
    protected $logger;

    public function __construct(Manager $manager = null, Logger $logger = null)
    {
        $this->manager = $manager ?? new Manager();
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'push-notifications';
    }

    /**
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface
    {
        return new NotificationsTopic();
    }

    /**
     * @return string
     */
    public function getTopicRegex(): string
    {
        return '.*';
    }

    /**
     * Called when there is a new notification
     * NOTE: the topic delays the delivery
     * @param EventInterface $event
     * @return booo
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof NotificationEvent) {
            return false;
        }

        $notification = $event->getNotification();

        if ($notification->getReadTimestamp()) {
            $this->logger->info("{$notification->getUrn()} already read");
            return true; // Already ready
        }

        $this->logger->info("{$notification->getUrn()} sending");
        $this->manager->sendPushNotification($notification);

        return true;
    }
}
