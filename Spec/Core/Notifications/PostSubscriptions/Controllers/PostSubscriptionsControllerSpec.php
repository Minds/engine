<?php

namespace Spec\Minds\Core\Notifications\PostSubscriptions\Controllers;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Notifications\PostSubscriptions\Controllers\PostSubscriptionsController;
use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use Minds\Core\Notifications\PostSubscriptions\Models\PostSubscription;
use Minds\Core\Notifications\PostSubscriptions\Services\PostSubscriptionsService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class PostSubscriptionsControllerSpec extends ObjectBehavior
{
    private Collaborator $postSubscriptionsServiceMock;
    private Collaborator $entitiesBuilderMock;

    public function let(PostSubscriptionsService $postSubscriptionsServiceMock, EntitiesBuilder $entitiesBuilderMock)
    {
        $this->beConstructedWith($postSubscriptionsServiceMock, $entitiesBuilderMock);
        $this->postSubscriptionsServiceMock = $postSubscriptionsServiceMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PostSubscriptionsController::class);
    }

    public function it_should_return_a_post_subscription()
    {
        $loggedInUser = new User();

        $this->entitiesBuilderMock->single(1)->willReturn(new User());

        $this->postSubscriptionsServiceMock->withUser(Argument::type(User::class))
            ->willReturn($this->postSubscriptionsServiceMock);

        $this->postSubscriptionsServiceMock->withEntity(Argument::type(User::class))
            ->willReturn($this->postSubscriptionsServiceMock);

        $this->postSubscriptionsServiceMock->get()
            ->willReturn(new PostSubscription(
                userGuid: 2,
                entityGuid: 1,
                frequency: PostSubscriptionFrequencyEnum::ALWAYS,
            ));

        $subscription = $this->getPostSubscription(1, $loggedInUser);
        $subscription->shouldBeAnInstanceOf(PostSubscription::class);
    }

    public function it_should_return_a_post_subscriptio_with_never_frequency()
    {
        $loggedInUser = new User();

        $this->entitiesBuilderMock->single(1)->willReturn(new User());

        $this->postSubscriptionsServiceMock->withUser(Argument::type(User::class))
            ->willReturn($this->postSubscriptionsServiceMock);

        $this->postSubscriptionsServiceMock->withEntity(Argument::type(User::class))
            ->willReturn($this->postSubscriptionsServiceMock);

        $this->postSubscriptionsServiceMock->get()
            ->willReturn(new PostSubscription(
                userGuid: 2,
                entityGuid: 1,
                frequency: PostSubscriptionFrequencyEnum::NEVER,
            ));

        $subscription = $this->getPostSubscription(1, $loggedInUser);
        $subscription->shouldBeAnInstanceOf(PostSubscription::class);
        $subscription->frequency->shouldBe(PostSubscriptionFrequencyEnum::NEVER);
    }

    public function it_should_update_post_subscription()
    {
        $loggedInUser = new User();

        $this->entitiesBuilderMock->single(1)->willReturn(new User());

        $this->postSubscriptionsServiceMock->withUser(Argument::type(User::class))
            ->willReturn($this->postSubscriptionsServiceMock);

        $this->postSubscriptionsServiceMock->withEntity(Argument::type(User::class))
            ->willReturn($this->postSubscriptionsServiceMock);

        $this->postSubscriptionsServiceMock->subscribe(PostSubscriptionFrequencyEnum::ALWAYS)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->postSubscriptionsServiceMock->get()
            ->willReturn(new PostSubscription(
                userGuid: 2,
                entityGuid: 1,
                frequency: PostSubscriptionFrequencyEnum::ALWAYS,
            ));

        $subscription = $this->updatePostSubscription(1, PostSubscriptionFrequencyEnum::ALWAYS, $loggedInUser);
        $subscription->shouldBeAnInstanceOf(PostSubscription::class);
        $subscription->frequency->shouldBe(PostSubscriptionFrequencyEnum::ALWAYS);
    }
}
