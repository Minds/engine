<?php
/**
 * This subscription will build notifications from stream events
 */
namespace Minds\Core\Notification;

use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;

class NotificationEventStreamsSubscription implements SubscriptionInterface
{
    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'notifications';
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
        return '.*'; // Notifications want all actions
    }

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return booo
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        // Do some stuff
        error_log('Notification event stream subscription says hello');
        
        $notification = new Notification();
        $notification->setToGuid($event->getEntity()->getOwnerGuid());
        $notification->setFromGuid($event->getUser()->getGuid());
        $notification->setEntityGuid($event->getEntity()->getGuid());

        switch ($event->getAction()) {
            case ActionEvent::ACTION_VOTE:
                $notification->setType('vote');
                break;
            case ActionEvent::ACTION_COMMENT:
                $notification->setType('comment');
                break;
        }

        // TODO: actually save the notification
        var_dump($notification);

        return true; // Return true to awknowledge the event from the stream (stop it being redelivered)
    }
}
