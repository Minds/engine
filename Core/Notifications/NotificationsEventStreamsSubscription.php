<?php
/**
 * This subscription will build notifications from stream events
 */
namespace Minds\Core\Notifications;

use AppendIterator;
use Minds\Common\SystemUser;
use Minds\Common\Urn;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipManager;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Sockets\Events as SocketEvents;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Wire\Wire;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use NoRewindIterator;

class NotificationsEventStreamsSubscription implements SubscriptionInterface
{
    /** @var Manager */
    protected $manager;

    /** @var Logger */
    protected $logger;

    /** @var Core\Config */
    protected $config;

    private ?GiftCardsManager $giftCardsManager = null;

    public function __construct(
        Manager $manager = null,
        Logger $logger = null,
        Config $config = null,
        private ?Resolver $entitiesResolver = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->manager = $manager ?? Di::_()->get('Notifications\Manager');
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->config = $config ?? Di::_()->get('Config');
        $this->entitiesResolver ??= Di::_()->get(Resolver::class);
        $this->entitiesBuilder ??= Di::_()->get("EntitiesBuilder");
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

        if ($event->getAction() === ActionEvent::ACTION_VOTE_DOWN) {
            return true; // True to acknowledge, but we dont care about down votes
        }

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

        $notifications = [];
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
                $notifications[] = $notification;
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
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_SUBSCRIBE:
                // Replace toGuid with the entity guid as the entity is the subscribed person
                $notification->setToGuid((string) $entity->getGuid());
                $notification->setType(NotificationTypes::TYPE_SUBSCRIBE);
                $notifications[] = $notification;
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
                $notifications[] = $notification;
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
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_BOOST_ACCEPTED:
                $notification->setType(NotificationTypes::TYPE_BOOST_ACCEPTED);
                $notification->setFromGuid(SystemUser::GUID);
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_BOOST_REJECTED:
                $notification->setType(NotificationTypes::TYPE_BOOST_REJECTED);
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setData([
                    'reason' => $event->getActionData()['boost_reject_reason'],
                ]);
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_BOOST_COMPLETED:
                $notification->setType(NotificationTypes::TYPE_BOOST_COMPLETED);
                $notification->setFromGuid(SystemUser::GUID);
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_TOKEN_WITHDRAW_ACCEPTED:
                $notification->setType(NotificationTypes::TYPE_TOKEN_WITHDRAW_ACCEPTED);
                // The entity is the Withdraw\Request
                $notification->setToGuid($entity->getUserGuid());
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setData([
                    'amount' => $entity->getAmount(),
                ]);
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_TOKEN_WITHDRAW_REJECTED:
                $notification->setType(NotificationTypes::TYPE_TOKEN_WITHDRAW_REJECTED);
                $notification->setToGuid($entity->getUserGuid());
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setData([
                    'amount' => $entity->getAmount(),
                ]);
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_GROUP_INVITE:
                $notification->setType(NotificationTypes::TYPE_GROUP_INVITE);
                $notification->setEntityUrn($event->getActionData()['group_urn']);
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_GROUP_QUEUE_ADD:
                $notification->setType(NotificationTypes::TYPE_GROUP_QUEUE_ADD);
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setData([
                    'group_urn' => $event->getActionData()['group_urn']
                ]);
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_GROUP_QUEUE_RECEIVED:
                try {
                    $notifications = array_merge(
                        $notifications,
                        $this->buildGroupQueueReceivedNotifications($event)
                    );
                } catch (\Exception $e) {
                    $this->logger->error($e);
                    return false;
                }
                break;
            case ActionEvent::ACTION_GROUP_QUEUE_APPROVE:
                $notification->setType(NotificationTypes::TYPE_GROUP_QUEUE_APPROVE);
                $notification->setData([
                    'group_urn' => $event->getActionData()['group_urn']
                ]);
                $notifications[] = $notification;
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
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_SUPERMIND_REQUEST_CREATE:
                $notification->setToGuid($entity->getReceiverGuid());
                $notification->setFromGuid($entity->getSenderGuid());
                $notification->setType(NotificationTypes::TYPE_SUPERMIND_REQUEST_CREATE);
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_SUPERMIND_REQUEST_ACCEPT:
                $notification->setToGuid($entity->getSenderGuid());
                $notification->setFromGuid($entity->getReceiverGuid());
                $notification->setType(NotificationTypes::TYPE_SUPERMIND_REQUEST_ACCEPT);
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_SUPERMIND_REQUEST_REJECT:
                $notification->setToGuid($entity->getSenderGuid());
                $notification->setFromGuid($entity->getReceiverGuid());
                $notification->setType(NotificationTypes::TYPE_SUPERMIND_REQUEST_REJECT);
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_SUPERMIND_REQUEST_EXPIRING_SOON:
                $notification->setToGuid($entity->getReceiverGuid());
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setType(NotificationTypes::TYPE_SUPERMIND_REQUEST_EXPIRING_SOON);
                $notifications[] = $notification;
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
                $notifications[] = $notification;
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
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_GIFT_CARD_RECIPIENT_NOTIFICATION:
                /**
                 * @type User $recipientUser
                 */
                $recipientUser = $entity;
                $notification->setToGuid($recipientUser->getGuid());
                $notification->setFromGuid(SystemUser::GUID);
                $notification->setType(NotificationTypes::TYPE_GIFT_CARD_RECIPIENT_NOTIFIED);

                $sender = $this->entitiesBuilder->single($event->getActionData()['sender_guid']);

                $giftCard = $this->getGiftCardsManager()->getGiftCard((int) $event->getActionData()['gift_card_guid']);

                $notification->setData([
                    'sender' => $sender->export(),
                    'gift_card' => get_object_vars($giftCard),
                ]);
                $notifications[] = $notification;
                break;
            case ActionEvent::ACTION_GIFT_CARD_ISSUER_CLAIMED_NOTIFICATION:
                $issuer = $entity;
                $claimantGuid = $event->getActionData()['claimant_guid'];
                $giftCardGuid = $event->getActionData()['gift_card_guid'];

                $claimant = $this->entitiesBuilder->single($claimantGuid);
                if (!$claimant || !($claimant instanceof User)) {
                    $this->logger->error("Gift card claimant not found with guid: $claimantGuid, skipping...");
                    return true;
                }

                $giftCard = $this->getGiftCardsManager()->getGiftCard((int) $giftCardGuid);
                if (!$giftCard) {
                    $this->logger->error("Gift card not found with guid: $giftCardGuid, skipping...");
                    return true;
                }

                $notification->setToGuid($issuer->getGuid())
                    ->setFromGuid(SystemUser::GUID)
                    ->setType(NotificationTypes::TYPE_GIFT_CARD_CLAIMED_ISSUER_NOTIFIED)
                    ->setData([
                        'gift_card' => get_object_vars($giftCard),
                        'claimant' => $claimant->export()
                    ]);
                $notifications[] = $notification;
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

        $allNotificationsSent = true;

        foreach ($notifications as $notification) {
            if (!$this->manager->add($notification)) {
                $allNotificationsSent = false;
                $this->logger->info("{$notification->getUuid()} {$notification->getType()} failed");
                continue;
            }
            $this->logger->info("{$notification->getUuid()} {$notification->getType()} saved");
            $this->emitToSockets($notification);
        }

        return $allNotificationsSent;
    }

    /**
     * Gets group membership manager from DI.
     * @return GroupMembershipManager returns group membership manager.
     */
    private function getGroupMembershipManager(): GroupMembershipManager
    {
        return Di::_()->get(GroupMembershipManager::class);
    }

    /**
     * Builds group queue received notifications to be sent to all moderators and owners.
     * @param ActionEvent $event - triggered action event.
     * @return Notification[] - array of notifications.
     */
    private function buildGroupQueueReceivedNotifications(ActionEvent $event): array
    {
        $group = $this->entitiesResolver->single(new Urn($event->getActionData()['group_urn']));

        if (!$group || !($group instanceof Group)) {
            throw new ServerErrorException('Group not found with urn: ' . $event->getActionData()['group_urn']);
        }

        $groupMembershipManager = $this->getGroupMembershipManager();
        $recipients = new AppendIterator();
        $recipients->append(new NoRewindIterator($groupMembershipManager->getMembers(
            group: $group,
            limit: 10,
            membershipLevel: GroupMembershipLevelEnum::MODERATOR
        )));
        $recipients->append(new NoRewindIterator($groupMembershipManager->getMembers(
            group: $group,
            limit: 10,
            membershipLevel: GroupMembershipLevelEnum::OWNER
        )));

        $notifications = [];

        foreach ($recipients as $recipient) {
            $notifications[] = (new Notification())
                ->setType(NotificationTypes::TYPE_GROUP_QUEUE_RECEIVED)
                ->setToGuid($recipient->userGuid)
                ->setFromGuid(SystemUser::GUID)
                ->setEntityUrn($group->getUrn());
        }

        return $notifications;
    }

    /**
     * Emits a users notification count via sockets.
     * @param Notification $notification - new notification.
     * @return void
     */
    private function emitToSockets(Notification $notification): void
    {
        try {
            $toUser = $this->entitiesBuilder->single($notification->getToGuid());

            if (!$toUser || !($toUser instanceof User)) {
                $this->logger->warning('User not found with guid: ' . $notification?->getToGuid() ?? 'unknown');
                return;
            }

            $count = $this->manager->getUnreadCount($toUser);
            $roomName = "notification:count:{$notification->getToGuid()}";

            (new SocketEvents())
                ->setRoom($roomName)
                ->emit($roomName, $count);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function getGiftCardsManager(): GiftCardsManager
    {
        return $this->giftCardsManager ??= Di::_()->get(GiftCardsManager::class);
    }
}
