<?php

namespace Spec\Minds\Core\Subscriptions;

use Minds\Core\Subscriptions\Delegates;
use Minds\Core\Subscriptions\Manager;
use Minds\Core\Subscriptions\Repository;
use Minds\Core\Subscriptions\Subscription;
use Minds\Core\Subscriptions\Relational;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $repository;
    private $copyToElasticSearchDelegate;
    private $sendNotificationDelegate;
    private $cacheDelegate;
    private $eventsDelegate;
    private $feedsDelegate;
    private $relationalRepo;


    public function let(
        Repository $repository,
        Delegates\CopyToElasticSearchDelegate $copyToElasticSearchDelegate,
        Delegates\SendNotificationDelegate $sendNotificationDelegate,
        Delegates\CacheDelegate $cacheDelegate,
        Delegates\EventsDelegate $eventsDelegate,
        Delegates\FeedsDelegate $feedsDelegate,
        Relational\Repository $relationalRepository
    ) {
        $this->beConstructedWith(
            $repository,
            $copyToElasticSearchDelegate,
            $sendNotificationDelegate,
            $cacheDelegate,
            $eventsDelegate,
            $feedsDelegate,
            $relationalRepository
        );
        $this->repository = $repository;
        $this->copyToElasticSearchDelegate = $copyToElasticSearchDelegate;
        $this->sendNotificationDelegate = $sendNotificationDelegate;
        $this->cacheDelegate = $cacheDelegate;
        $this->eventsDelegate = $eventsDelegate;
        $this->feedsDelegate = $feedsDelegate;
        $this->relationalRepo = $relationalRepository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    // NOT IMPLEMENTED
    /*function it_should_say_not_subscribed()
    {
        $this->repository->get(Argument::that(function($subscription) {

        });

        $this->isSubscribed()
            ->shouldBe(false);
    }

    function it_should_say_subscribed()
    {
        $this->isSubscribed()
            ->shouldBe(true);
    }*/

    public function it_should_subscribe()
    {
        // Confusing.. but this is the returned subscription
        // post repository
        $subscription = new Subscription;
        $subscription->setActive(true);

        $this->repository->add(Argument::that(function ($sub) {
            return $sub->getSubscriberGuid() == 123
                && $sub->getPublisherGuid() == 456;
        }))
            ->shouldBeCalled()
            ->willReturn($subscription);

        $publisher = (new User)->set('guid', 456);
        $this->setSubscriber((new User)->set('guid', 123));

        // Call the send notification delegate
        $this->sendNotificationDelegate->send($subscription)
            ->shouldBeCalled();

        // Call the events delegate
        $this->eventsDelegate->trigger($subscription)
            ->shouldBeCalled();

        // Call the es delegate
        $this->copyToElasticSearchDelegate->copy($subscription)
            ->shouldBeCalled();

        // Call the cache delegate
        $this->cacheDelegate->cache($subscription)
            ->shouldBeCalled();

        // Add to the new sql engine
        $this->relationalRepo->add($subscription)
            ->shouldBeCalled();

        $newSubscription = $this->subscribe($publisher);
        $newSubscription->isActive()
            ->shouldBe(true);
    }

    public function it_should_not_allow_if_over_5000_subscriptions(User $subscriber)
    {
        $publisher = (new User)->set('guid', 456);

        $subscriber->getSubscriptionsCount()
            ->willReturn(5000);
        $subscriber->getGUID()
            ->willReturn(123);
        $this->setSubscriber($subscriber);

        $this->shouldThrow('Minds\Core\Subscriptions\TooManySubscriptionsException')
            ->duringSubscribe($publisher);
    }

    public function it_should_unsubscribe()
    {
        // Confusing.. but this is the returned subscription
        // post repository
        $subscription = new Subscription;
        $subscription->setActive(false);

        $this->repository->delete(Argument::that(function ($sub) {
            return $sub->getSubscriberGuid() == 123
                && $sub->getPublisherGuid() == 456;
        }))
            ->shouldBeCalled()
            ->willReturn($subscription);

        $publisher = (new User)->set('guid', 456);
        $this->setSubscriber((new User)->set('guid', 123));

        // Call the events delegate
        $this->eventsDelegate->trigger($subscription)
            ->shouldBeCalled();

        // Call the feeds delegate
        $this->feedsDelegate->remove($subscription)
            ->shouldBeCalled();

        // Call the es delegate
        $this->copyToElasticSearchDelegate->remove($subscription)
            ->shouldBeCalled();

        // Call the cache delegate
        $this->cacheDelegate->cache($subscription)
            ->shouldBeCalled();

        $this->relationalRepo->delete($subscription)
            ->shouldBeCalled();

        $newSubscription = $this->unSubscribe($publisher);
        $newSubscription->isActive()
            ->shouldBe(false);
    }
}
