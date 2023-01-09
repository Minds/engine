<?php

namespace Spec\Minds\Core\Notifications\Push;

use Minds\Core\Config\Config;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notifications\Push\PushNotification;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class PushNotificationSpec extends ObjectBehavior
{
    public $notification;
    public $config;
    public $experimentsManager;

    public function let(
        Notification $notification,
        Config $config,
        ExperimentsManager $experimentsManager
    ) {
        $this->beConstructedWith($notification, $config, $experimentsManager);
        $this->notification = $notification;
        $this->config = $config;
        $this->experimentsManager = $experimentsManager;
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

    public function it_should_get_title_for_a_boost_accepted_notification(
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
            ->willReturn(NotificationTypes::TYPE_BOOST_ACCEPTED);

        $this->getTitle()
            ->shouldReturn('Your Boost is now running');
    }

    public function it_should_get_title_for_a_boost_rejected_notification(
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
            ->willReturn(NotificationTypes::TYPE_BOOST_REJECTED);

        $this->getTitle()
            ->shouldReturn('Your Boost was rejected');
    }


    public function it_should_get_title_for_a_boost_completed_notification(
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
            ->willReturn(NotificationTypes::TYPE_BOOST_COMPLETED);

        $this->getTitle()
            ->shouldReturn('Your Boost is complete');
    }

    public function it_should_get_text_for_supermind_request_create(
        User $sender,
        Activity $entity
    ) {
        $sender->getName()
            ->shouldBeCalled()
            ->willReturn('MindsUser');

        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->notification->getMergedCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_SUPERMIND_REQUEST_CREATE);
        
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_SUPERMIND_REQUEST_CREATE);
            
        $this->getTitle()->shouldReturn('MindsUser sent you a Supermind offer');
    }

    public function it_should_get_text_for_supermind_request_accept(
        User $sender,
        Activity $entity
    ) {
        $sender->getName()
            ->shouldBeCalled()
            ->willReturn('MindsUser');

        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->notification->getMergedCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_SUPERMIND_REQUEST_ACCEPT);
        
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_SUPERMIND_REQUEST_ACCEPT);
            
        $this->getTitle()->shouldReturn('MindsUser has replied to your Supermind offer');
    }

    public function it_should_get_text_for_supermind_request_reject(
        User $sender,
        Activity $entity
    ) {
        $sender->getName()
            ->shouldBeCalled()
            ->willReturn('MindsUser');

        $this->notification->getFrom()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->notification->getMergedCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->notification->getToGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_SUPERMIND_REQUEST_REJECT);
        
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_SUPERMIND_REQUEST_REJECT);
            
        $this->getTitle()->shouldReturn('MindsUser has declined your Supermind offer');
    }

    public function it_should_get_body_for_a_boost_accepted_notification()
    {
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_BOOST_ACCEPTED);

        $this->getBody()->shouldReturn('');
    }

    public function it_should_get_body_for_a_boost_rejected_notification()
    {
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_BOOST_REJECTED);

        $this->getBody()->shouldReturn('');
    }

    public function it_should_get_body_for_a_boost_completed_notification()
    {
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_BOOST_COMPLETED);

        $this->getBody()->shouldReturn('');
    }

    public function it_should_get_uri_for_a_boost_accepted_notification_when_dynamic_boost_experiment_is_on()
    {
        $this->experimentsManager->isOn('epic-293-dynamic-boost')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('https://www.minds.com/');
        
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_BOOST_ACCEPTED);

        $this->notification->getData()
            ->shouldBeCalled()
            ->willReturn([
                'boost_location' => 2
            ]);

        $this->getUri()->shouldReturn('https://www.minds.com/boost/console-v2?state=approved&location=sidebar');
    }

    public function it_should_get_uri_for_a_boost_accepted_notification_when_dynamic_boost_experiment_is_off()
    {
        $this->experimentsManager->isOn('epic-293-dynamic-boost')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('https://www.minds.com/');
        
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_BOOST_ACCEPTED);

        $this->getUri()->shouldReturn('https://www.minds.com/boost/console/newsfeed/history');
    }

    public function it_should_get_uri_for_a_boost_completed_notification_when_dynamic_boost_experiment_is_on()
    {
        $this->experimentsManager->isOn('epic-293-dynamic-boost')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('https://www.minds.com/');
        
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_BOOST_COMPLETED);

        $this->notification->getData()
            ->shouldBeCalled()
            ->willReturn([
                'boost_location' => 1
            ]);

        $this->getUri()->shouldReturn('https://www.minds.com/boost/console-v2?state=completed&location=newsfeed');
    }

    public function it_should_get_uri_for_a_boost_completed_notification_when_dynamic_boost_experiment_is_off()
    {
        $this->experimentsManager->isOn('epic-293-dynamic-boost')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('https://www.minds.com/');
        
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_BOOST_COMPLETED);

        $this->getUri()->shouldReturn('https://www.minds.com/boost/console/newsfeed/history');
    }

    public function it_should_get_uri_for_a_boost_rejected_notification_for_an_activity(
        Activity $entity
    ) {
        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('https://www.minds.com/');
        
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_BOOST_REJECTED);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->getUri()->shouldReturn('https://www.minds.com/newsfeed/123');
    }

    public function it_should_get_uri_for_a_boost_rejected_notification_for_a_channel(
        User $entity
    ) {
        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('https://www.minds.com/');
        
        $this->notification->getType()
            ->shouldBeCalled()
            ->willReturn(NotificationTypes::TYPE_BOOST_REJECTED);

        $entity->getType()
            ->shouldBeCalled()
            ->willReturn('user');

        $entity->getUsername()
            ->shouldBeCalled()
            ->willReturn('testuser');

        $this->notification->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->getUri()->shouldReturn('https://www.minds.com/testuser');
    }
}
