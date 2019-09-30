<?php

namespace Spec\Minds\Core\Subscriptions\Requests;

use Minds\Core\Subscriptions\Requests\Manager;
use Minds\Core\Subscriptions\Requests\Repository;
use Minds\Core\Subscriptions\Requests\SubscriptionRequest;
use Minds\Core\Subscriptions\Requests\Delegates;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $repository;
    private $notificationsDelegate;
    private $subscriptionsDelegate;
    private $entitiesBuilder;

    public function let(
        Repository $repository,
        Delegates\NotificationsDelegate $notificationsDelegate,
        Delegates\SubscriptionsDelegate $subscriptionsDelegate,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->beConstructedWith($repository, $notificationsDelegate, $subscriptionsDelegate, $entitiesBuilder);
        $this->repository = $repository;
        $this->notificationsDelegate = $notificationsDelegate;
        $this->subscriptionsDelegate = $subscriptionsDelegate;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_add_a_request()
    {
        $subscriptionRequest = new SubscriptionRequest();
        $subscriptionRequest->setPublisherGuid(123)
            ->setSubscriberGuid(456);

        $this->entitiesBuilder->single(123)
            ->willReturn(new User);
        
        $this->repository->get("urn:subscription-request:123-456")
            ->willReturn(null);

        $this->repository->add($subscriptionRequest)
            ->willReturn(true);

        $this->notificationsDelegate->onAdd($subscriptionRequest)
            ->shouldBeCalled();

        $this->add($subscriptionRequest)
            ->shouldBe(true);
    }

    public function it_should_accept_a_request()
    {
        $subscriptionRequest = new SubscriptionRequest();
        $subscriptionRequest->setPublisherGuid(123)
            ->setSubscriberGuid(456);
        
        $this->repository->get("urn:subscription-request:123-456")
            ->willReturn($subscriptionRequest);

        $this->repository->delete(Argument::any())
            ->willReturn(true);

        $this->notificationsDelegate->onAccept($subscriptionRequest)
            ->shouldBeCalled();

        $this->subscriptionsDelegate->onAccept($subscriptionRequest)
            ->shouldBeCalled();

        $this->accept($subscriptionRequest)
            ->shouldBe(true);
    }

    public function it_should_decline_a_request()
    {
        $subscriptionRequest = new SubscriptionRequest();
        $subscriptionRequest->setPublisherGuid(123)
            ->setSubscriberGuid(456);
        
        $this->repository->get("urn:subscription-request:123-456")
            ->willReturn($subscriptionRequest);

        $this->repository->update(Argument::that(function ($sr) {
            return $sr->isDeclined() === true;
        }))
            ->willReturn(true);

        $this->notificationsDelegate->onDecline($subscriptionRequest)
            ->shouldBeCalled();

        $this->decline($subscriptionRequest)
            ->shouldBe(true);
    }
}
