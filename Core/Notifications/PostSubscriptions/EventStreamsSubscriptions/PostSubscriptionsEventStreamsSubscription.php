<?php
/**
 * You can test by running `php cli.php EventStreams --subscription=Core\\Notifications\\PostSubscriptions\\EventsStreamsSubscriptions\\PostSubscriptionsEventStreamsSubscription`
 */
namespace Minds\Core\Notifications\PostSubscriptions\EventStreamsSubscriptions;

use Minds\Core\Di\Di;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;
use Minds\Core\Entities\Ops\EntitiesOpsTopic;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notifications\Manager as NotificationsManager;
use Minds\Core\Notifications\PostSubscriptions\Services\PostSubscriptionsService;
use Minds\Entities\Activity;
use Minds\Entities\User;

class PostSubscriptionsEventStreamsSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?PostSubscriptionsService $service = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?NotificationsManager $notificationsManager = null,
        private ?Logger $logger = null,
    ) {
        $this->service ??= Di::_()->get(PostSubscriptionsService::class);
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
        $this->notificationsManager ??= Di::_()->get('Notifications\Manager');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'post-subscriptions';
    }

    /**
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface
    {
        return new EntitiesOpsTopic();
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
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof EntitiesOpsEvent) {
            $this->logger->warning("Not an EntitiesOpsEvent", ['event_type' => get_class($event)]); // @codeCoverageIgnore
            return false;
        }

        if ($event->getOp() !== EntitiesOpsEvent::OP_CREATE) {
            // We only care about creates
            return true;
        }

        if ($event->getTimestamp() < time() - 3600) {
            // Don't notify for event older than 1 hour, here
            return true;
        }

        $entity = $this->entitiesBuilder->getByUrn($event->getEntityUrn());

        if (!$entity instanceof Activity) {
            // We only care about activity posts
            return true;
        }

        $owner = $this->entitiesBuilder->single($entity->getOwnerGuid());

        if (!$owner instanceof User) {
            // Invalid owner
            return true;
        }

        $this->logger->info("{$entity->getUrn()} dispatching");

        // Get an iterator of all user guids who will receive the notification

        foreach ($this->service->withEntity($owner)->getAllForEntity() as $postSubscription) {
            $notification = new Notification();

            $notification->setFromGuid((string) $owner->getGuid());
            $notification->setEntityUrn($entity->getUrn());
            $notification->setType(NotificationTypes::TYPE_POST_SUBSCRIPTION);
            $notification->setToGuid($postSubscription->userGuid);

            $this->notificationsManager->add($notification);
        }

        return false;
    }
}
