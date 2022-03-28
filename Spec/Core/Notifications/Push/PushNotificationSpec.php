<?php

namespace Spec\Minds\Core\Notifications\Push;

use Minds\Core\Config\Config;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\Push\PushNotification;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class PushNotificationSpec extends ObjectBehavior
{
    public $notification;
    public $config;

    public function let(
        Notification $notification,
        Config $config
    ) {
        $this->beConstructedWith($notification, $config);
        $this->notification = $notification;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PushNotification::class);
    }

    public function it_should_get_title_for_subscribed_to_you_notification(
        User $sender,
        User $entity
    ) {
        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn('user');

        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn('subscribe');

        $sender->getName()
            ->shouldBeCalled()
            ->willReturn('Sender');

        $this->notification->getMergedCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->getTitle()
            ->shouldReturn('Sender subscribed to you');
    }

    public function it_should_get_title_for_commented_on_your_post_notification(
        User $sender,
        Activity $entity
    ) {
        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn('comment');

        
        $sender->getName()
            ->shouldBeCalled()
            ->willReturn('Sender');

        $this->notification->getMergedCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->getTitle()
            ->shouldReturn('Sender commented on your post');
    }

    public function it_should_get_title_for_commented_on_their_post_notification(
        User $sender,
        Activity $entity
    ) {
        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(321);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn('comment');

        
        $sender->getName()
            ->shouldBeCalled()
            ->willReturn('Sender');

        $this->notification->getMergedCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->getTitle()
            ->shouldReturn('Sender commented on their post');
    }

    public function it_should_get_title_for_voted_up_your_post_notification(
        User $sender,
        Activity $entity
    ) {
        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn('vote_up');

        
        $sender->getName()
            ->shouldBeCalled()
            ->willReturn('Sender');

        $this->notification->getMergedCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->getTitle()
            ->shouldReturn('Sender voted up your post');
    }

    public function it_should_get_title_for_voted_down_your_post_notification(
        User $sender,
        Activity $entity
    ) {
        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn('vote_down');

        $sender->getName()
            ->shouldBeCalled()
            ->willReturn('Sender');

        $this->notification->getMergedCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->getTitle()
            ->shouldReturn('Sender voted down your post');
    }
    
    public function it_should_get_title_for_reminded_your_post_notification(
        User $sender,
        Activity $entity
    ) {
        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn('remind');

        $sender->getName()
            ->shouldBeCalled()
            ->willReturn('Sender');

        $this->notification->getMergedCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->getTitle()
            ->shouldReturn('Sender reminded your post');
    }

    public function it_should_get_title_for_tagged_you_in_your_post_notification(
        User $sender,
        Activity $entity
    ) {
        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn('tag');

        $sender->getName()
            ->shouldBeCalled()
            ->willReturn('Sender');

        $this->notification->getMergedCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->getTitle()
            ->shouldReturn('Sender tagged you in your post');
    }

    public function it_should_get_title_for_tagged_you_in_their_post_notification(
        User $sender,
        Activity $entity
    ) {
        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(321);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn('tag');

        $sender->getName()
            ->shouldBeCalled()
            ->willReturn('Sender');

        $this->notification->getMergedCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->getTitle()
            ->shouldReturn('Sender tagged you in their post');
    }
    
    public function it_should_get_title_for_token_rewards_notification(
        User $sender,
        Activity $entity
    ) {
        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn('token_rewards_summary');

        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn('token_rewards_summary');

        $this->getTitle()
            ->shouldReturn('Minds Token Rewards');
    }
}
