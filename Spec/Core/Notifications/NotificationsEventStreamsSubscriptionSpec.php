<?php

namespace Spec\Minds\Core\Notifications;

use Minds\Common\SystemUser;
use Minds\Core\Boost\Network\Boost;
use Minds\Entities\Boost\Peer;
use Minds\Core\Config;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\Notifications\Manager;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationsEventStreamsSubscription;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Rewards\Withdraw\Request;
use Minds\Core\Wire\Wire;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotificationsEventStreamsSubscriptionSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    /** @var Config */
    protected $config;

    public function let(Manager $manager, Config $config)
    {
        $this->beConstructedWith($manager, null, $config);
        $this->manager = $manager;
        $this->config = $config;
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

    public function it_should_send_peer_boost_request(ActionEvent $actionEvent, Peer $peerBoost, User $peerBoostOwner, User $peerBoostDestination)
    {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_BOOST_PEER_REQUEST);

        $actionEvent->getUser()
            ->willReturn($peerBoostOwner);

        $actionEvent->getEntity()
            ->willReturn($peerBoost);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        //

        $peerBoost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('456');

        $peerBoost->getDestination()
            ->willReturn($peerBoostDestination);

        $peerBoost->getBid()
            ->willReturn('10');

        $peerBoost->getType()
            ->willReturn('tokens');

        //

        $peerBoostDestination->getGuid()
            ->willReturn('123');

        $peerBoostOwner->getGuid()
            ->willReturn('456');

        $peerBoost->getUrn()
            ->willReturn('urn:peer-boost:guid');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_BOOST_PEER_REQUEST
                && $notification->getToGuid() === '123'
                && $notification->getFromGuid() === '456'
                && $notification->getData()['bid'] === '10'
                && $notification->getData()['type'] === 'tokens';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent);
    }

    public function it_should_send_peer_boost_accepted(ActionEvent $actionEvent, Peer $peerBoost, User $peerBoostOwner, User $peerBoostDestination)
    {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_BOOST_PEER_ACCEPTED);

        $actionEvent->getUser()
            ->willReturn($peerBoostDestination);

        $actionEvent->getEntity()
            ->willReturn($peerBoost);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        //

        $peerBoost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('456');

        $peerBoost->getBid()
            ->willReturn('10');

        $peerBoost->getType()
            ->willReturn('tokens');

        $peerBoost->getUrn()
            ->willReturn('urn:peer-boost:guid');

        //

        $peerBoostDestination->getGuid()
            ->willReturn('123');

        $peerBoostOwner->getGuid()
            ->willReturn('456');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_BOOST_PEER_ACCEPTED
                && $notification->getToGuid() === '456'
                && $notification->getFromGuid() === '123'
                && $notification->getData()['bid'] === '10'
                && $notification->getData()['type'] === 'tokens';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->consume($actionEvent);
    }

    public function it_should_send_peer_boost_rejected(ActionEvent $actionEvent, Peer $peerBoost, User $peerBoostOwner, User $peerBoostDestination)
    {
        $actionEvent->getAction()
            ->willReturn(ActionEvent::ACTION_BOOST_PEER_REJECTED);

        $actionEvent->getUser()
            ->willReturn($peerBoostDestination);

        $actionEvent->getEntity()
            ->willReturn($peerBoost);

        $actionEvent->getTimestamp()
            ->willReturn(time());

        //

        $peerBoost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('456');

        $peerBoost->getBid()
            ->willReturn('10');

        $peerBoost->getType()
            ->willReturn('tokens');

        $peerBoost->getUrn()
            ->willReturn('urn:peer-boost:guid');

        //

        $peerBoostDestination->getGuid()
            ->willReturn('123');

        $peerBoostOwner->getGuid()
            ->willReturn('456');

        $this->manager->add(Argument::that(function (Notification $notification) {
            return $notification->getType() === NotificationTypes::TYPE_BOOST_PEER_REJECTED
                && $notification->getToGuid() === '456'
                && $notification->getFromGuid() === '123'
                && $notification->getData()['bid'] === '10'
                && $notification->getData()['type'] === 'tokens';
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
}
