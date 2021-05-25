<?php

namespace Spec\Minds\Core\Subscriptions\Delegates;

use Minds\Core\Subscriptions\Delegates\EventsDelegate;
use Minds\Core\Subscriptions\Subscription;
use Minds\Core\Events\EventsDispatcher;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EventsDelegateSpec extends ObjectBehavior
{
    private $eventsDispatcher;

    public function let(EventsDispatcher $eventsDispatcher)
    {
        $this->beConstructedWith($eventsDispatcher);
        $this->eventsDispatcher = $eventsDispatcher;
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

        $this->trigger($subscription);
    }
}
