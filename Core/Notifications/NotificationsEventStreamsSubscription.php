<?php
/**
 * This subscription will build notifications from stream events
 */
namespace Minds\Core\Notifications;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Helpers\Urn;

class NotificationsEventStreamsSubscription implements SubscriptionInterface
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
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        if ($event->getEntity()->getOwnerGuid() == $event->getUser()->getGuid()) {
            $this->logger->info('Skipping as owner is sender');
            return true; // True to acknowledge, but we dont care about interactions with our own posts
        }

        $notification = new Notification();

        $entityUrn = $event->getEntity()->getUrn();
        $notification->setEntityUrn($entityUrn);

        $entityUrnNid = Urn::getNid($entityUrn);

        // ojm ask mark about how this is organized
        // can we rename user to actor?
        if ($entityUrnNid === 'user') {
            $notification->setToGuid((string) $event->getEntity()->getGuid());
            // ojm how to handle when there is no user or FromGuid?
            $notification->setFromGuid((string) $event->getUser()->getGuid());
        } else {
            // Do this when the notification is related to something published (activity post/comment)
            $notification->setToGuid((string) $event->getEntity()->getOwnerGuid());
            $notification->setFromGuid((string) $event->getUser()->getGuid());
        }

        switch ($event->getAction()) {
            case ActionEvent::ACTION_VOTE_UP:
                $notification->setType(NotificationTypes::TYPE_VOTE_UP);
                break;
            case ActionEvent::ACTION_VOTE_DOWN:
                $notification->setType(NotificationTypes::TYPE_VOTE_DOWN);
                break;
            case ActionEvent::ACTION_COMMENT:
                $notification->setType(NotificationTypes::TYPE_COMMENT);
                $notification->setData([
                    'comment_urn' => $event->getActionData()['comment_urn'],
                ]);
                break;
            case ActionEvent::ACTION_TAG:
                // Replace toGuid with the entity guid as the entity is the tagged person
+               $notification->setToGuid((string) $notification->getEntity()->getGuid());
                // Replace entity_urn with the post guid,
                $notification->setEntityUrn($event->getActionData()['tag_in_entity_urn']);
                $notification->setType(NotificationTypes::TYPE_TAG);
                break;
            case ActionEvent::ACTION_SUBSCRIBE:
                $notification->setType(NotificationTypes::TYPE_SUBSCRIBE);
                break;
            case ActionEvent::ACTION_REFERRAL_PING:
                return true; // Don't send notification until referrals are fixed
                // $notification->setType(NotificationTypes::TYPE_REFERRAL_PING);
                // break;
            case ActionEvent::ACTION_REFERRAL_PENDING:
                return true; // Don't send notification until referrals are fixed
                // $notification->setType(NotificationTypes::TYPE_REFERRAL_PENDING);
                // break;
            case ActionEvent::ACTION_REFERRAL_COMPLETE:
                return true; // Don't send notification until referrals are fixed
                // $notification->setType(NotificationTypes::TYPE_REFERRAL_COMPLETE);
                // break;
            case ActionEvent::ACTION_REMIND:
                $notification->setType(NotificationTypes::TYPE_REMIND);
                $notification->setData([
                    'remind_urn' => $event->getActionData()['remind_urn'],
                ]);
                break;
            case ActionEvent::ACTION_QUOTE:
                $notification->setType(NotificationTypes::TYPE_QUOTE);
                $notification->setData([
                    'quote_urn' => $event->getActionData()['quote_urn'],
                ]);
                // Replace entity_urn with our quote
                $notification->setEntityUrn($event->getActionData()['quote_urn']);
                break;
            default:
                return true; // We will not make a notification from this
        }

        // Save and submit
        if ($this->manager->add($notification)) {

            // Some logging
            $this->logger->info("{$notification->getUuid()} {$notification->getType()} saved");

            return true; // Return true to acknowledge the event from the stream (stop it being redelivered)
        }

        return false;
    }
}
