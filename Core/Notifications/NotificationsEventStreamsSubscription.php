<?php
/**
 * This subscription will build notifications from stream events
 */
namespace Minds\Core\Notifications;

use Minds\Common\SystemUser;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Wire\Wire;
use Minds\Entities\User;

class NotificationsEventStreamsSubscription implements SubscriptionInterface
{
    /** @var Manager */
    protected $manager;

    /** @var Logger */
    protected $logger;

    /** @var Core\Config */
    protected $config;

    public function __construct(Manager $manager = null, Logger $logger = null, Config $config = null)
    {
        $this->manager = $manager ?? Di::_()->get('Notifications\Manager');
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->config = $config ?? Di::_()->get('Config');
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
            $this->logger->info('Skipping as not an action event');
            return false;
        }

        $this->logger->info('Action event type: ' . $event->getAction());

        /** @var User */
        $user = $event->getUser();

        /** @var mixed */
        $entity = $event->getEntity();

        if ($entity->getOwnerGuid() == $user->getGuid()
            && !$entity instanceof Wire // Wire owners are the senders
            && !$entity instanceof SupermindRequest // Supermind entity owners are the senders
            && $event->getAction() !== ActionEvent::ACTION_GROUP_QUEUE_ADD // Actor is post owner
        ) {
            $this->logger->info('Skipping as owner is sender');
            return true; // True to acknowledge, but we dont care about interactions with our own posts
        }

        if ($event->getTimestamp() < time() - 3600) {
            // Don't notify for event older than 1 hour, here
            return true;
        }

        $notification = new Notification();

        $notification->setFromGuid((string) $user->getGuid());
        $notification->setEntityUrn($entity->getUrn());

        if ($event->getEntity() instanceof User) {
            $notification->setToGuid((string) $entity->getGuid());
        } else {
            // Do this when the notification is related to something published (activity post/comment)
            $notification->setToGuid((string) $entity->getOwnerGuid());
        }

        switch ($event->getAction()) {
            case ActionEvent::ACTION_VOTE_UP:
                $notification->setType(NotificationTypes::TYPE_VOTE_UP);
                break;
            case ActionEvent::ACTION_VOTE_DOWN:
                $notification->setType(NotificationTypes::TYPE_VOTE_DOWN);
                break;
            case ActionEvent::ACTION_COMMENT:
                // Comment notifications are handled via their own EventStreamSubscription
                // due to more tailored delivery specifications, so true to awknowledge but don't deliver
                return true;
            case ActionEvent::ACTION_TAG:
                // Replace toGuid with the entity guid as the entity is the tagged person
                $notification->setToGuid((string) $event->getEntity()->getGuid());
                // Replace entity_urn with the post guid,
                $notification->setEntityUrn($event->getActionData()['tag_in_entity_urn']);
                $notification->setType(NotificationTypes::TYPE_TAG);
                break;
            case ActionEvent::ACTION_SUBSCRIBE:
                // Replace toGuid with the entity guid as the entity is the subscribed person
                $notification->setToGuid((string) $entity->getGuid());
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
                if ($event->getActionData()['is_supermind_reply'] ?? false) {
                    return true; // Do not send as we will be sending a Supermind reply notification afterward.
                }
                $notification->setType(NotificationTypes::TYPE_QUOTE);
                $notification->setData([
                    'quote_urn' => $event->getActionData()['quote_urn'],
                ]);
                // Replace entity_urn with our quote
                $notification->setEntityUrn($event->getActionData()['quote_urn']);
                break;
            case ActionEvent::ACTION_BOOST_ACCEPTED:
                $notification->setType(NotificationTypes::TYPE_BOOST_ACCEPTED);
                $notification->setFromGuid(SystemUser::GUID);
                break;
            case ActionEvent::ACTION_BOOST_REJECTED:
                $notification->setType(NotificationTypes::TYPE_BOOST_REJECTED);
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setData([
                    'reason' => $event->getActionData()['boost_reject_reason'],
                ]);
                break;
            case ActionEvent::ACTION_BOOST_COMPLETED:
                $notification->setType(NotificationTypes::TYPE_BOOST_COMPLETED);
                $notification->setFromGuid(SystemUser::GUID);
                break;
            case ActionEvent::ACTION_TOKEN_WITHDRAW_ACCEPTED:
                $notification->setType(NotificationTypes::TYPE_TOKEN_WITHDRAW_ACCEPTED);
                // The entity is the Withdraw\Request
                $notification->setToGuid($entity->getUserGuid());
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setData([
                    'amount' => $entity->getAmount(),
                ]);
                break;
            case ActionEvent::ACTION_TOKEN_WITHDRAW_REJECTED:
                $notification->setType(NotificationTypes::TYPE_TOKEN_WITHDRAW_REJECTED);
                $notification->setToGuid($entity->getUserGuid());
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setData([
                    'amount' => $entity->getAmount(),
                ]);
                break;
            case ActionEvent::ACTION_GROUP_INVITE:
                $notification->setType(NotificationTypes::TYPE_GROUP_INVITE);
                $notification->setEntityUrn($event->getActionData()['group_urn']);
                break;
            case ActionEvent::ACTION_GROUP_QUEUE_ADD:
                $notification->setType(NotificationTypes::TYPE_GROUP_QUEUE_ADD);
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setData([
                    'group_urn' => $event->getActionData()['group_urn']
                ]);
                break;
            case ActionEvent::ACTION_GROUP_QUEUE_APPROVE:
                $notification->setType(NotificationTypes::TYPE_GROUP_QUEUE_APPROVE);
                $notification->setData([
                    'group_urn' => $event->getActionData()['group_urn']
                ]);
                break;
                // Doesn't work bc post gets deleted immediately when rejected
                // case ActionEvent::ACTION_GROUP_QUEUE_REJECT:
            //     $notification->setType(NotificationTypes::TYPE_GROUP_QUEUE_REJECT);
            //     $notification->setData([
            //         'group_urn' => $event->getActionData()['group_urn']
            //     ]);
            //     break;
            case ActionEvent::ACTION_WIRE_SENT:
                /** @var Wire */
                $wire = $entity;
                $isPlusPayout = (string) $wire->getSender()->getGuid() === (string) $this->config->get('plus')['handler'] ?? '';
                $isProPayout = (string) $wire->getSender()->getGuid() === (string) $this->config->get('pro')['handler'] ?? '';

                if ($isPlusPayout || $isProPayout) {
                    $notification->setType(NotificationTypes::TYPE_WIRE_PAYOUT);
                } else {
                    $notification->setType(NotificationTypes::TYPE_WIRE_RECEIVED);
                }

                $notification->setToGuid($wire->getReceiver()->getGuid());

                $notification->setData([
                    'wire_urn' => $wire->getUrn(),
                    'amount' => $wire->getAmount(),
                    'method' => $wire->getMethod(),
                ]);
                break;
            case ActionEvent::ACTION_SUPERMIND_REQUEST_CREATE:
                $notification->setToGuid($entity->getReceiverGuid());
                $notification->setFromGuid($entity->getSenderGuid());
                $notification->setType(NotificationTypes::TYPE_SUPERMIND_REQUEST_CREATE);
                break;
            case ActionEvent::ACTION_SUPERMIND_REQUEST_ACCEPT:
                $notification->setToGuid($entity->getSenderGuid());
                $notification->setFromGuid($entity->getReceiverGuid());
                $notification->setType(NotificationTypes::TYPE_SUPERMIND_REQUEST_ACCEPT);
                break;
            case ActionEvent::ACTION_SUPERMIND_REQUEST_REJECT:
                $notification->setToGuid($entity->getSenderGuid());
                $notification->setFromGuid($entity->getReceiverGuid());
                $notification->setType(NotificationTypes::TYPE_SUPERMIND_REQUEST_REJECT);
                break;
            case ActionEvent::ACTION_SUPERMIND_REQUEST_EXPIRING_SOON:
                $notification->setToGuid($entity->getReceiverGuid());
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setType(NotificationTypes::TYPE_SUPERMIND_REQUEST_EXPIRING_SOON);
                break;
            case ActionEvent::ACTION_AFFILIATE_EARNINGS_DEPOSITED:
                /**
                 * @type User $affiliateUser
                 */
                $affiliateUser = $entity;
                $notification->setToGuid($affiliateUser->getGuid());
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setType(NotificationTypes::TYPE_AFFILIATE_EARNINGS_DEPOSITED);
                $notification->setData($event->getActionData());
                break;
            case ActionEvent::ACTION_REFERRER_AFFILIATE_EARNINGS_DEPOSITED:
                /**
                 * @type User $affiliateUser
                 */
                $affiliateUser = $entity;
                $notification->setToGuid($affiliateUser->getGuid());
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setType(NotificationTypes::TYPE_REFERRER_AFFILIATE_EARNINGS_DEPOSITED);
                $notification->setData($event->getActionData());
                break;
            // case ActionEvent::ACTION_SUPERMIND_REQUEST_EXPIRE:
            //     $notification->setToGuid($entity->getSenderGuid());
            //     $notification->setFromGuid($entity->getReceiverGuid());
            //     $notification->setType(NotificationTypes::TYPE_SUPERMIND_REQUEST_EXPIRE);
            //     break;
            default:
                $this->logger->info("{$event->getAction()} is not a valid action for notifications");
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
