<?php

namespace Spec\Minds\Core\Suggestions;

use Minds\Entities\User;
use Minds\Common\Repository\Response;
use Minds\Core\Suggestions\Manager;
use Minds\Core\Suggestions\Suggestion;
use Minds\Core\Suggestions\Repository;
use Minds\Core\Subscriptions\Manager as SubscriptionsManager;
use Minds\Core\Features;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\RateLimits\InteractionsLimiter;
use Minds\Core\Security\RateLimits\RateLimit;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    private $repository;
    /** @var EntitiesBuilder */
    private $entitiesBuilder;
    /** @var InteractionsLimiter */
    private $interactionsLimiter;
    /** @var SubscriptionsManager */
    private $subscriptionsManager;

    public function let(
        Repository $repository,
        EntitiesBuilder $entitiesBuilder,
        SubscriptionsManager $subscriptionsManager,
        InteractionsLimiter $interactionsLimiter,
        Features\Manager $features
    ) {
        $this->repository = $repository;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->interactionsLimiter = $interactionsLimiter;
        $this->subscriptionsManager = $subscriptionsManager;

        $features->has('suggestions')
            ->willReturn(true);

        $this->beConstructedWith($repository, $entitiesBuilder, null, $subscriptionsManager, $interactionsLimiter, $features);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_a_list_of_suggested_users()
    {
        $response = new Response();
        $response[] = (new Suggestion)
            ->setEntityGuid(456);

        $response[] = (new Suggestion)
            ->setEntityGuid(789);

        $this->subscriptionsManager->setSubscriber(Argument::any())
            ->willReturn($this->subscriptionsManager);

        $this->subscriptionsManager->getSubscriptionsCount()
            ->willReturn(10);

        $this->interactionsLimiterMock(100);

        $this->repository->getList([
            'limit' => 24 * 3,
            'paging-token' => '',
            'user_guid' => 123,
            'type' => 'user',
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->setUser((new User)->set('guid', 123));

        $this->entitiesBuilder->single(456)
            ->shouldBeCalled()
            ->willReturn((new User)->set('guid', 456));

        $this->entitiesBuilder->single(789)
            ->shouldBeCalled()
            ->willReturn((new User)->set('guid', 789));

        $newResponse = $this->getList(['limit' => 24]);

        $newResponse[0]->getEntityGuid()
            ->shouldBe(456);
        $newResponse[0]->getEntity()->getGuid()
            ->shouldBe('456');

        $newResponse[1]->getEntityGuid()
            ->shouldBe(789);
        $newResponse[1]->getEntity()->getGuid()
            ->shouldBe('789');
    }

    public function it_shouldnt_return_a_list_of_suggested_users_if_close_too_close_to_the_rate_limit_threshold()
    {
        $this->setUser((new User)->set('guid', 123));

        $this->interactionsLimiterMock(5);

        $newResponse = $this->getList(['limit' => 24]);

        $newResponse->count()->shouldBe(0);
    }

    /**
     * @return bool
     */
    private function interactionsLimiterMock(int $remaining = 20)
    {
        $this->interactionsLimiter->getRemainingAttempts(Argument::any(), Argument::any())->willReturn($remaining);
    }
}
