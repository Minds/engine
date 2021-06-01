<?php

namespace Spec\Minds\Core\Subscriptions\Delegates;

use Minds\Core\Subscriptions\Delegates\EventsDelegate;
use Minds\Core\Subscriptions\Subscription;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EventsDelegateSpec extends ObjectBehavior
{
    private $eventsDispatcher;
    private $actionEventsTopic;

    public function let(EventsDispatcher $eventsDispatcher, ActionEventsTopic $actionEventsTopic)
    {
        $this->beConstructedWith($eventsDispatcher, $actionEventsTopic);

        $this->eventsDispatcher = $eventsDispatcher;
        $this->actionEventsTopic = $actionEventsTopic;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EventsDelegate::class);
    }

    public function it_should_trigger_an_active_subscription_event()
    {
        $subscriber = new User();
        $subscriber->set('guid', 123);

        $publisher = new User();
        $publisher->set('guid', 456);

        $subscription = new Subscription;
        $subscription->setSubscriber($subscriber)
            ->setPublisher($publisher)
            ->setActive(true);

        $this->eventsDispatcher->trigger('subscribe', 'all', [
            'user_guid' => 123,
            'to_guid' => 456,
            'subscription' => $subscription,
        ])
            ->shouldBeCalled();

        $this->actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return $actionEvent->getAction() === 'subscribe';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->trigger($subscription);
    }

    public function it_should_trigger_an_unsubscribe_event()
    {
        $subscriber = new User();
        $subscriber->set('guid', 123);

        $publisher = new User();
        $publisher->set('guid', 456);

        $subscription = new Subscription;
        $subscription->setSubscriber($subscriber)
            ->setPublisher($publisher)
            ->setActive(false);

        $this->eventsDispatcher->trigger('unsubscribe', 'all', [
            'user_guid' => 123,
            'to_guid' => 456,
            'subscription' => $subscription,
        ])
            ->shouldBeCalled();

        $this->actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return $actionEvent->getAction() === 'unsubscribe';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->trigger($subscription);
    }
}
