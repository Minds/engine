<?php

namespace Spec\Minds\Core\Notifications;

use DateTime;
use Minds\Common\SystemUser;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipManager;
use Minds\Core\Groups\V2\Membership\Membership;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications\Manager;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationsEventStreamsSubscription;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Rewards\Withdraw\Request;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Wire\Wire;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use RedisMock;

class NotificationsEventStreamsSubscriptionSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    /** @var Config */
    protected $config;

    /** @var Logger */
    protected $logger;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Resolver */
    protected $entitiesResolver;

    /** @var GroupMembershipManager */
    protected $groupMembershipManager;

    /** @var RedisMock */
    protected $redisMock;

    /** @var GiftCardsManager */
    protected $giftCardsManager;

    public function let(
        Manager $manager,
        Logger $logger,
        Config $config,
        EntitiesBuilder $entitiesBuilder,
        Resolver $entitiesResolver,
        GroupMembershipManager $groupMembershipManager,
        RedisMock $redisMock,
        GiftCardsManager $giftCardsManager
    ) {
        $this->manager = $manager;
        $this->logger = $logger;
        $this->config = $config;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->entitiesResolver = $entitiesResolver;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->groupMembershipManager = $groupMembershipManager;
        $this->redisMock = $redisMock;
        $this->giftCardsManager = $giftCardsManager;

        $this->beConstructedWith($manager, $logger, $config, $entitiesResolver, $entitiesBuilder);

        Di::_()->bind(GroupMembershipManager::class, function () use ($groupMembershipManager) {
            return $groupMembershipManager->getWrappedObject();
        });

        // Used within socket events.
        Di::_()->bind('PubSub\Redis', function () use ($redisMock) {
            return $redisMock->getWrappedObject();
        });

        Di::_()->bind(GiftCardsManager::class, function () use ($giftCardsManager) {
            return $giftCardsManager->getWrappedObject();
        });
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(NotificationsEventStreamsSubscription::class);
    }

    /**
     * Boost notifications
     */

    public function it_should_send_if_admin_rejects_boost(ActionEvent $actionEvent, Boost $boost, User $admin, User $boostOwner)
    {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_BOOST_REJECTED);

        $actionEvent->getUser()
            ->willReturn($admin);

        $actionEvent->getEntity()
            ->willReturn($boost);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getActionData()
            ->willReturn([
                'boost_reject_reason' => 4
            ]);

        //

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('456');

        $boost->getUrn()
            ->willReturn('urn:boost:network:guid');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_BOOST_REJECTED
                && $notification->getToGuid() === '456'
                && $notification->getFromGuid() === SystemUser::GUID
                && $notification->getData()['reason'] === 4;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent);
    }

    public function it_should_send_if_admin_accepts_boost(ActionEvent $actionEvent, Boost $boost, User $admin, User $boostOwner)
    {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_BOOST_ACCEPTED);

        $actionEvent->getUser()
            ->willReturn($admin);

        $actionEvent->getEntity()
            ->willReturn($boost);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getActionData()
            ->willReturn([
                'boost_location' => 1
            ]);

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('456');

        $boost->getUrn()
            ->willReturn('urn:boost:network:guid');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_BOOST_ACCEPTED
                && $notification->getToGuid() === '456'
                && $notification->getFromGuid() === SystemUser::GUID;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent);
    }

    public function it_should_send_if_boost_completion_event_is_passed(ActionEvent $actionEvent, Boost $boost, User $admin, User $boostOwner)
    {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_BOOST_COMPLETED);

        $actionEvent->getUser()
            ->willReturn($admin);

        $actionEvent->getEntity()
            ->willReturn($boost);


        $actionEvent->getActionData()
            ->willReturn([
                'boost_location' => 1
            ]);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('456');

        $boost->getUrn()
            ->willReturn('urn:boost:network:guid');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_BOOST_COMPLETED
                && $notification->getToGuid() === '456'
                && $notification->getFromGuid() === SystemUser::GUID;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent);
    }

    /**
     * Withdrawal notifications
     */

    public function it_should_send_withdraw_accepted(ActionEvent $actionEvent, Request $withdrawRequest, User $admin, User $requester)
    {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_TOKEN_WITHDRAW_ACCEPTED);

        $actionEvent->getUser()
            ->willReturn($admin);

        $actionEvent->getEntity()
            ->willReturn($withdrawRequest);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        //

        $withdrawRequest->getOwnerGuid()
            ->willReturn('123');
        $withdrawRequest->getUserGuid()
            ->willReturn('123');
        $withdrawRequest->getAmount()
            ->willReturn('100');
        $withdrawRequest->getUrn()
            ->willReturn('urn:withdraw-request:123-' . time() . '-tx');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_TOKEN_WITHDRAW_ACCEPTED
                && $notification->getToGuid() === '123'
                && $notification->getFromGuid() === SystemUser::GUID
                && $notification->getData()['amount'] === '100';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent);
    }

    public function it_should_send_withdraw_rejected(ActionEvent $actionEvent, Request $withdrawRequest, User $admin, User $requester)
    {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_TOKEN_WITHDRAW_REJECTED);

        $actionEvent->getUser()
            ->willReturn($admin);

        $actionEvent->getEntity()
            ->willReturn($withdrawRequest);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        //

        $withdrawRequest->getOwnerGuid()
            ->willReturn('123');
        $withdrawRequest->getUserGuid()
            ->willReturn('123');
        $withdrawRequest->getAmount()
            ->willReturn('100');
        $withdrawRequest->getUrn()
            ->willReturn('urn:withdraw-request:123-' . time() . '-tx');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_TOKEN_WITHDRAW_REJECTED
                && $notification->getToGuid() === '123'
                && $notification->getFromGuid() === SystemUser::GUID
                && $notification->getData()['amount'] === '100';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent);
    }

    /**
     * Group invite notifications
     */
    public function it_should_send_group_invite(ActionEvent $actionEvent, Group $group, User $actor, User $receiver)
    {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_GROUP_INVITE);

        $actionEvent->getEntity()
            ->willReturn($receiver);

        $actionEvent->getUser()
            ->willReturn($actor);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getActionData()
            ->willReturn([
                'group_urn' => 'urn:group:789'
            ]);

        //

        $actor->getGuid()
            ->willReturn('456');

        $receiver->getGuid()
            ->willReturn('123');

        $receiver->getOwnerGuid()
            ->willReturn(0);

        $receiver->getUrn()
            ->willReturn('urn:user:123');

        //
        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_GROUP_INVITE
                && $notification->getToGuid() === '123'
                && $notification->getFromGuid() === '456'
                && $notification->getEntityUrn() === 'urn:group:789';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent);
    }

    /**
     * Group queue notifications
     */
    public function it_should_send_group_queue_add_to_self(ActionEvent $actionEvent, Activity $activity, User $actor, User $receiver)
    {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_GROUP_QUEUE_ADD);

        $actionEvent->getEntity()
            ->willReturn($activity);

        $actionEvent->getUser()
            ->willReturn($actor);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getActionData()
            ->willReturn([
                'group_urn' => 'urn:group:789'
            ]);

        //

        $actor->getGuid()
            ->willReturn('456');

        $receiver->getGuid()
            ->willReturn('123');

        $receiver->getOwnerGuid()
            ->willReturn(0);

        $activity->getOwnerEntity()
            ->willReturn($receiver);
        $activity->getOwnerGuid()
            ->willReturn('123');
        $activity->getUrn()
            ->willReturn('urn:activity:123');

        //
        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_GROUP_QUEUE_ADD
                && $notification->getToGuid() === '123'
                && $notification->getFromGuid() === SystemUser::GUID;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent);
    }

    public function it_should_send_group_queue_received_notifications(
        ActionEvent $actionEvent,
        Activity $activity,
        User $actor,
        User $owner,
        User $moderator1,
        User $moderator2,
        Group $group
    ) {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_GROUP_QUEUE_RECEIVED);

        $actionEvent->getEntity()
            ->willReturn($activity);

        $actionEvent->getUser()
            ->willReturn($actor);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getActionData()
            ->willReturn([
                'group_urn' => 'urn:group:789'
            ]);

        //

        $actor->getGuid()
            ->willReturn('456');

        $moderator1->getGuid()
            ->willReturn('123');

        $moderator2->getOwnerGuid()
            ->willReturn(0);

        $activity->getOwnerEntity()
            ->willReturn($moderator1);
        $activity->getOwnerGuid()
            ->willReturn('123');
        $activity->getUrn()
            ->willReturn('urn:activity:123');

        $group->getUrn()
            ->shouldBeCalled()
            ->willReturn('urn:group:234');

        $this->entitiesResolver->single(Argument::any())
            ->shouldBeCalled()
            ->willReturn($group);

        $refTime = time();

        $this->groupMembershipManager->getMembers(
            $group,
            GroupMembershipLevelEnum::MODERATOR,
            false,
            10,
            Argument::any(),
            Argument::any()
        )
            ->shouldBeCalled()
            ->willYield([
                (new Membership(
                    groupGuid: 123,
                    userGuid: 111,
                    createdTimestamp: new DateTime("@$refTime"),
                    membershipLevel: GroupMembershipLevelEnum::MEMBER,
                )),
                (new Membership(
                    groupGuid: 123,
                    userGuid: 222,
                    createdTimestamp: new DateTime("@$refTime"),
                    membershipLevel: GroupMembershipLevelEnum::OWNER,
                ))
            ]);

        $this->groupMembershipManager->getMembers(
            $group,
            GroupMembershipLevelEnum::OWNER,
            false,
            10,
            Argument::any(),
            Argument::any()
        )
            ->shouldBeCalled()
            ->willYield([
                (new Membership(
                    groupGuid: 123,
                    userGuid: 333,
                    createdTimestamp: new DateTime("@$refTime"),
                    membershipLevel: GroupMembershipLevelEnum::OWNER,
                ))
            ]);

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_GROUP_QUEUE_RECEIVED
                && $notification->getToGuid() == '111'
                && $notification->getFromGuid() === SystemUser::GUID
                && $notification->getEntityUrn() === 'urn:group:234';
        }));

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_GROUP_QUEUE_RECEIVED
                && $notification->getToGuid() == '222'
                && $notification->getFromGuid() === SystemUser::GUID
                && $notification->getEntityUrn() === 'urn:group:234';
        }));

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_GROUP_QUEUE_RECEIVED
                && $notification->getToGuid() == '333'
                && $notification->getFromGuid() === SystemUser::GUID
                && $notification->getEntityUrn() === 'urn:group:234';
        }));

        $this->consume($actionEvent);
    }

    public function it_should_send_group_queue_accept(ActionEvent $actionEvent, Activity $activity, User $actor, User $receiver)
    {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_GROUP_QUEUE_APPROVE);

        $actionEvent->getEntity()
            ->willReturn($activity);

        $actionEvent->getUser()
            ->willReturn($actor);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getActionData()
            ->willReturn([
                'group_urn' => 'urn:group:789'
            ]);

        //

        $actor->getGuid()
            ->willReturn('456');

        $receiver->getGuid()
            ->willReturn('123');

        $receiver->getOwnerGuid()
            ->willReturn(0);

        $activity->getOwnerEntity()
            ->willReturn($receiver);
        $activity->getOwnerGuid()
            ->willReturn('123');
        $activity->getUrn()
            ->willReturn('urn:activity:123');

        //
        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_GROUP_QUEUE_APPROVE
                && $notification->getToGuid() === '123'
                && $notification->getFromGuid() === '456';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent);
    }

    // Disabled because post is deleted immediately when rejected
    // public function it_should_send_group_queue_reject(ActionEvent $actionEvent, Activity $activity, User $actor, User $receiver)
    // {
    //     $actionEvent->getAction()
    //         ->willReturn(ActionEvent::ACTION_GROUP_QUEUE_REJECT);

    //     $actionEvent->getEntity()
    //         ->willReturn($activity);

    //     $actionEvent->getUser()
    //         ->willReturn($actor);

    //     $actionEvent->getTimestamp()
    //         ->willReturn(time());

    //     $actionEvent->getActionData()
    //         ->willReturn([
    //             'group_urn' => 'urn:group:789'
    //         ]);

    //     //

    //     $actor->getGuid()
    //         ->willReturn('456');

    //     $receiver->getGuid()
    //         ->willReturn('123');

    //     $receiver->getOwnerGuid()
    //         ->willReturn(0);

    //     $activity->getOwnerEntity()
    //         ->willReturn($receiver);
    //     $activity->getOwnerGuid()
    //         ->willReturn('123');
    //     $activity->getUrn()
    //         ->willReturn('urn:activity:123');

    //     //
    //     $this->manager->add(Argument::that(function (Notification $notification) {
    //         return $notification->getType() === NotificationTypes::TYPE_GROUP_QUEUE_REJECT
    //             && $notification->getToGuid() === '123'
    //             && $notification->getFromGuid() === '456';
    //     }))
    //         ->shouldBeCalled()
    //         ->willReturn(true);

    //     $this->consume($actionEvent);
    // }

    /**
     * Wire notifications
     */

    public function it_should_send_wire_received(ActionEvent $actionEvent, Wire $wire, User $sender, User $receiver)
    {
        $this->config->get('plus')
            ->willReturn([
                'handler' => '121'
            ]);

        $this->config->get('pro')
            ->willReturn([
                'handler' => '122'
            ]);

        //

        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_WIRE_SENT);

        $actionEvent->getEntity()
            ->willReturn($wire);

        $actionEvent->getUser()
            ->willReturn($sender);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        //

        $wire->getOwnerGuid()
            ->willReturn('123');

        $wire->getSender()
            ->willReturn($sender);

        $wire->getReceiver()
            ->willReturn($receiver);

        $wire->getUrn()
            ->willReturn('urn:wire:urn-here');

        $wire->getAmount()
            ->willReturn(10);

        $wire->getMethod()
            ->willReturn('usd');

        //

        $sender->getGuid()
            ->willReturn('123');

        $receiver->getGuid()
            ->willReturn('456');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_WIRE_RECEIVED
                && $notification->getData()['wire_urn'] === 'urn:wire:urn-here'
                && $notification->getData()['amount'] === 10
                && $notification->getData()['method'] === 'usd';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent)->shouldBe(true);
    }

    public function it_should_send_wire_payout(ActionEvent $actionEvent, Wire $wire, User $sender, User $receiver)
    {
        $this->config->get('plus')
            ->willReturn([
                'handler' => '121'
            ]);

        $this->config->get('pro')
            ->willReturn([
                'handler' => '122'
            ]);

        $actionEvent->getAction()
            ->shouldBeCalled()
            ->willReturn(ActionEvent::ACTION_WIRE_SENT);

        $actionEvent->getEntity()
            ->shouldBeCalled()
            ->willReturn($wire);

        $actionEvent->getUser()
            ->shouldBeCalled()
            ->willReturn($sender);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        //

        $wire->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $wire->getSender()
            ->shouldBeCalled()
            ->willReturn($sender);

        $wire->getReceiver()
            ->shouldBeCalled()
            ->willReturn($receiver);

        $wire->getUrn()
            ->shouldBeCalled()
            ->willReturn('urn:wire:urn-here');

        $wire->getAmount()
            ->shouldBeCalled()
            ->willReturn(10);

        $wire->getMethod()
            ->shouldBeCalled()
            ->willReturn('usd');

        //

        $sender->getGuid()
            ->willReturn('122');

        $receiver->getGuid()
            ->willReturn('456');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_WIRE_PAYOUT
                && $notification->getData()['wire_urn'] === 'urn:wire:urn-here'
                && $notification->getData()['amount'] === 10
                && $notification->getData()['method'] === 'usd';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent)->shouldBe(true);
    }

    public function it_should_send_supermind_request_create(ActionEvent $actionEvent, SupermindRequest $request, User $sender, User $receiver)
    {
        $actionEvent->getAction()
            ->shouldBeCalled()
            ->willReturn(ActionEvent::ACTION_SUPERMIND_REQUEST_CREATE);

        $actionEvent->getEntity()
            ->shouldBeCalled()
            ->willReturn($request);

        $actionEvent->getUser()
            ->shouldBeCalled()
            ->willReturn($sender);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        //

        $request->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $request->getUrn()
            ->shouldBeCalled()
            ->willReturn('urn:supermind:urn-here');

        $request->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn('123');


        $request->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn('321');
        //

        $sender->getGuid()
            ->willReturn('122');

        $receiver->getGuid()
            ->willReturn('456');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_SUPERMIND_REQUEST_CREATE
                && $notification->getToGuid() === '123'
                && $notification->getFromGuid() === '321';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent)->shouldBe(true);
    }

    public function it_should_send_supermind_request_accept(ActionEvent $actionEvent, SupermindRequest $request, User $sender, User $receiver)
    {
        $actionEvent->getAction()
            ->shouldBeCalled()
            ->willReturn(ActionEvent::ACTION_SUPERMIND_REQUEST_ACCEPT);

        $actionEvent->getEntity()
            ->shouldBeCalled()
            ->willReturn($request);

        $actionEvent->getUser()
            ->shouldBeCalled()
            ->willReturn($sender);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        //

        $request->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $request->getUrn()
            ->shouldBeCalled()
            ->willReturn('urn:supermind:urn-here');

        $request->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn('123');


        $request->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn('321');
        //

        $sender->getGuid()
            ->willReturn('122');

        $receiver->getGuid()
            ->willReturn('456');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_SUPERMIND_REQUEST_ACCEPT
                && $notification->getToGuid() === '321'
                && $notification->getFromGuid() === '123';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent)->shouldBe(true);
    }

    public function it_should_send_supermind_request_reject(ActionEvent $actionEvent, SupermindRequest $request, User $sender, User $receiver)
    {
        $actionEvent->getAction()
            ->shouldBeCalled()
            ->willReturn(ActionEvent::ACTION_SUPERMIND_REQUEST_REJECT);

        $actionEvent->getEntity()
            ->shouldBeCalled()
            ->willReturn($request);

        $actionEvent->getUser()
            ->shouldBeCalled()
            ->willReturn($sender);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        //

        $request->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $request->getUrn()
            ->shouldBeCalled()
            ->willReturn('urn:supermind:urn-here');

        $request->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn('123');


        $request->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn('321');
        //

        $sender->getGuid()
            ->willReturn('122');

        $receiver->getGuid()
            ->willReturn('456');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_SUPERMIND_REQUEST_REJECT
                && $notification->getToGuid() === '321'
                && $notification->getFromGuid() === '123';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent)->shouldBe(true);
    }

    /**
     * Quote notifications
     */
    public function it_should_send_a_quote_post_notification(ActionEvent $actionEvent, Activity $activity, User $user)
    {
        $quoteUrn = 'urn:activity:123';
        $activityUrn = 'urn:activity:456';

        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_QUOTE);

        $actionEvent->getUser()
            ->willReturn($user);

        $actionEvent->getEntity()
            ->willReturn($activity);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getActionData()
            ->willReturn([
                'quote_urn' => $quoteUrn
            ]);

        //

        $ownerGuid = '456';
        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($ownerGuid);

        $activity->getUrn()
            ->willReturn($activityUrn);

        $this->manager->add(Argument::that(function (Notification $notification) use ($ownerGuid) {
            return $notification->getType() === NotificationTypes::TYPE_QUOTE
                && $notification->getToGuid() === $ownerGuid
                && $notification->getUrn() === 'urn:notification:456-';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent);
    }

    public function it_should_NOT_send_a_quote_post_notification_for_supermind_replies(ActionEvent $actionEvent, Activity $activity, User $user)
    {
        $quoteUrn = 'urn:activity:123';
        $activityUrn = 'urn:activity:456';

        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_QUOTE);

        $actionEvent->getUser()
            ->willReturn($user);

        $actionEvent->getEntity()
            ->willReturn($activity);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getActionData()
            ->willReturn([
                'quote_urn' => $quoteUrn,
                'is_supermind_reply' => true
            ]);

        //

        $ownerGuid = '456';
        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($ownerGuid);

        $activity->getUrn()
            ->willReturn($activityUrn);

        $this->manager->add(Argument::any())
            ->shouldNotBeCalled();

        $this->consume($actionEvent)->shouldBe(true);
    }

    /**
     * Gift card notifications
     */
    public function it_should_send_a_gift_card_recipient_notification(
        ActionEvent $actionEvent,
        User $issuer,
        User $user,
        GiftCard $giftCard
    ) {
        $senderGuid = '1234567890123456';
        $giftCardGuid = '2234567890123456';
        $ownerGuid = '3234567890123456';
        $ownerUrn = 'urn:user:3234567890123456';
        $issuerGuid = '4234567890123456';

        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_GIFT_CARD_RECIPIENT_NOTIFICATION);

        $actionEvent->getUser()
            ->willReturn($user);

        $actionEvent->getEntity()
            ->willReturn($issuer);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getActionData()
            ->shouldBeCalled()
            ->willReturn([
                'sender_guid' => $senderGuid,
                'gift_card_guid' => $giftCardGuid
            ]);

        $issuer->getGuid()
            ->shouldBeCalled()
            ->willReturn($issuerGuid);

        $issuer->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($ownerGuid);

        $issuer->getUrn()
            ->willReturn($ownerUrn);

        $this->entitiesBuilder->single($senderGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->giftCardsManager->getGiftCard((int) $giftCardGuid)
            ->shouldBeCalled()
            ->willReturn($giftCard);

        $this->manager->add(Argument::that(function (Notification $notification) use ($issuerGuid) {
            return $notification->getType() === NotificationTypes::TYPE_GIFT_CARD_RECIPIENT_NOTIFIED
                && $notification->getToGuid() === $issuerGuid
                && $notification->getFromGuid() === SystemUser::GUID
                && $notification->getUrn() === 'urn:notification:4234567890123456-';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single(Argument::any())
            ->shouldBeCalled()
            ->willReturn($user);

        $this->manager->getUnreadCount($user)
            ->shouldBeCalled()
            ->willReturn(0);

        $this->consume($actionEvent);
    }

    public function it_should_send_a_gift_card_issuer_claimed_notification(
        ActionEvent $actionEvent,
        User $claimant,
        User $issuer,
        GiftCard $giftCard
    ) {
        $claimantGuid = '1234567890123456';
        $giftCardGuid = '2234567890123456';
        $claimantUrn = 'urn:user:3234567890123456';
        $issuerGuid = '4234567890123456';
        $issuerUrn = 'urn:user:4234567890123456';

        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_GIFT_CARD_ISSUER_CLAIMED_NOTIFICATION);

        $actionEvent->getUser()
            ->willReturn($claimant);

        $actionEvent->getEntity()
            ->willReturn($issuer);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getActionData()
            ->shouldBeCalled()
            ->willReturn([
                'claimant_guid' => $claimantGuid,
                'gift_card_guid' => $giftCardGuid
            ]);

        $claimant->getGuid()
            ->shouldBeCalled()
            ->willReturn($claimantGuid);

        $claimant->getUrn()
            ->willReturn($claimantUrn);

        $issuer->getGuid()
            ->shouldBeCalled()
            ->willReturn($issuerGuid);

        $issuer->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($issuerGuid);

        $issuer->getUrn()
            ->willReturn($issuerUrn);

        $claimant->export()
            ->willReturn([]);

        $this->entitiesBuilder->single($claimantGuid)
            ->shouldBeCalled()
            ->willReturn($claimant);

        $this->giftCardsManager->getGiftCard((int) $giftCardGuid)
            ->shouldBeCalled()
            ->willReturn($giftCard);

        $this->manager->add(Argument::that(function (Notification $notification) use ($issuerGuid) {
            return $notification->getType() === NotificationTypes::TYPE_GIFT_CARD_CLAIMED_ISSUER_NOTIFIED
                && $notification->getToGuid() === $issuerGuid
                && $notification->getFromGuid() === SystemUser::GUID
                && $notification->getUrn() === 'urn:notification:4234567890123456-';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single(Argument::any())
            ->shouldBeCalled()
            ->willReturn($issuer);

        $this->manager->getUnreadCount($issuer)
            ->shouldBeCalled()
            ->willReturn(0);

        $this->consume($actionEvent);
    }

    /**
     * Socket emission
     */
    public function it_should_emit_to_sockets_after_sending(
        ActionEvent $actionEvent,
        Activity $activity,
        User $user,
        User $toUser
    ) {
        $quoteUrn = 'urn:activity:123';
        $activityUrn = 'urn:activity:456';

        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_QUOTE);

        $actionEvent->getUser()
            ->willReturn($user);

        $actionEvent->getEntity()
            ->willReturn($activity);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        $actionEvent->getActionData()
            ->willReturn([
                'quote_urn' => $quoteUrn
            ]);

        $ownerGuid = '456';
        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($ownerGuid);

        $activity->getUrn()
            ->willReturn($activityUrn);

        $this->manager->add(Argument::that(function (Notification $notification) use ($ownerGuid) {
            return $notification->getType() === NotificationTypes::TYPE_QUOTE
                && $notification->getToGuid() === $ownerGuid
                && $notification->getUrn() === 'urn:notification:456-';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilder->single(Argument::any())
            ->shouldBeCalled()
            ->willReturn($toUser);

        $this->manager->getUnreadCount($toUser)
            ->shouldBeCalled()
            ->willReturn(12);

        $this->redisMock->publish("socket.io#/#", Argument::that(function ($arg) {
            return strpos($arg, 'notification:count:456');
        }))
            ->shouldBeCalled();

        $this->consume($actionEvent);
    }

}
