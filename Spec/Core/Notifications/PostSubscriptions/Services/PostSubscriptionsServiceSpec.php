<?php

namespace Spec\Minds\Core\Notifications\PostSubscriptions\Services;

use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use Minds\Core\Notifications\PostSubscriptions\Models\PostSubscription;
use Minds\Core\Notifications\PostSubscriptions\Repositories\PostSubscriptionsRepository;
use Minds\Core\Notifications\PostSubscriptions\Services\PostSubscriptionsService;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class PostSubscriptionsServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;

    public function let(PostSubscriptionsRepository $repositoryMock)
    {
        $this->beConstructedWith($repositoryMock);

        $this->repositoryMock = $repositoryMock;
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(PostSubscriptionsService::class);
    }
    
    public function it_should_subscribe_to_another_user()
    {
        $user = new User();
        $entity = new User();
    
        $this->repositoryMock->upsert(Argument::type(PostSubscription::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->withUser($user)->withEntity($entity)->subscribe(PostSubscriptionFrequencyEnum::ALWAYS)
            ->shouldBe(true);
    }

    public function it_should_subscribe_to_a_group()
    {
        $user = new User();
        $entity = new Group();
    
        $this->repositoryMock->upsert(Argument::type(PostSubscription::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->withUser($user)->withEntity($entity)->subscribe(PostSubscriptionFrequencyEnum::ALWAYS)
            ->shouldBe(true);
    }

    public function it_should_subscribe_to_a_post()
    {
        $user = new User();
        $entity = new Activity();
    
        $this->repositoryMock->upsert(Argument::type(PostSubscription::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->withUser($user)->withEntity($entity)->subscribe(PostSubscriptionFrequencyEnum::ALWAYS)
            ->shouldBe(true);
    }

    public function it_should_return_true_if_subscribed_always()
    {
        $user = new User();
        $entity = new User();
    
        $this->repositoryMock->get(Argument::type('integer'), Argument::type('integer'))
            ->shouldBeCalled()
            ->willReturn(
                new PostSubscription(
                    userGuid: 1,
                    entityGuid: 2,
                    frequency: PostSubscriptionFrequencyEnum::ALWAYS,
                )
            );

        $this->withUser($user)->withEntity($entity)->isSubscribed()
            ->shouldBe(true);
    }

    public function it_should_return_true_if_subscribed_highlights()
    {
        $user = new User();
        $entity = new User();
    
        $this->repositoryMock->get(Argument::type('integer'), Argument::type('integer'))
            ->shouldBeCalled()
            ->willReturn(
                new PostSubscription(
                    userGuid: 1,
                    entityGuid: 2,
                    frequency: PostSubscriptionFrequencyEnum::HIGHLIGHTS,
                )
            );

        $this->withUser($user)->withEntity($entity)->isSubscribed()
            ->shouldBe(true);
    }

    public function it_should_return_false_if_subscribed_never()
    {
        $user = new User();
        $entity = new User();
    
        $this->repositoryMock->get(Argument::type('integer'), Argument::type('integer'))
            ->shouldBeCalled()
            ->willReturn(
                new PostSubscription(
                    userGuid: 1,
                    entityGuid: 2,
                    frequency: PostSubscriptionFrequencyEnum::NEVER,
                )
            );

        $this->withUser($user)->withEntity($entity)->isSubscribed()
            ->shouldBe(false);
    }

    public function it_should_return_subscription()
    {
        $user = new User();
        $entity = new User();
    
        $this->repositoryMock->get(Argument::type('integer'), Argument::type('integer'))
            ->shouldBeCalled()
            ->willReturn(
                new PostSubscription(
                    userGuid: 1,
                    entityGuid: 2,
                    frequency: PostSubscriptionFrequencyEnum::ALWAYS,
                )
            );

        $subscription = $this->withUser($user)->withEntity($entity)->get();
        $subscription->shouldBeAnInstanceOf(PostSubscription::class);
        $subscription->frequency->shouldBe(PostSubscriptionFrequencyEnum::ALWAYS);
    }

    public function it_should_always_return_subscription_even_if_not_in_database()
    {
        $user = new User();
        $entity = new User();
    
        $this->repositoryMock->get(Argument::type('integer'), Argument::type('integer'))
            ->shouldBeCalled()
            ->willReturn(
                null
            );

        $subscription = $this->withUser($user)->withEntity($entity)->get();
        $subscription->shouldBeAnInstanceOf(PostSubscription::class);
        $subscription->frequency->shouldBe(PostSubscriptionFrequencyEnum::NEVER);
    }
}
