<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Recommendations\Algorithms\SuggestedChannels;

use Minds\Common\Repository\Response;
use Minds\Core\Subscriptions\Relational\Repository;
use PhpSpec\ObjectBehavior;
use Minds\Core\Recommendations\Algorithms\AlgorithmOptions;
use Minds\Core\Recommendations\Algorithms\SuggestedChannels\SuggestedChannelsRecommendationsAlgorithm;
use Minds\Core\Suggestions\Suggestion;
use Minds\Entities\User;
use PhpSpec\Wrapper\Collaborator;
use Psr\SimpleCache\CacheInterface;

class SuggestedChannelsRecommendationsAlgorithmSpec extends ObjectBehavior
{
    private Collaborator $options;
    private Collaborator $repository;
    private Collaborator $cache;

    public function let(
        AlgorithmOptions $options,
        Repository $repository,
        CacheInterface $cache
    ) {
        $this->options = $options;
        $this->repository = $repository;
        $this->cache = $cache;

        $this->beConstructedWith($options, $repository, $cache);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(SuggestedChannelsRecommendationsAlgorithm::class);
    }

    public function it_should_get_recommendations(
        User $user,
        User $suggestedUser1,
        User $suggestedUser2,
        User $suggestedUser3
    ): void {
        $limit = 3;
        $userGuid = '1234567890123451';

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->setUser($user);

        $this->cache->get("suggested-channels-offset::$userGuid", 0)
            ->shouldBeCalled()
            ->willReturn(0);

        $this->repository->getSubscriptionsOfSubscriptions(
            userGuid: $userGuid,
            limit: $limit,
            offset: 0,
        )->shouldBeCalled()
            ->willYield([
                $suggestedUser1,
                $suggestedUser2,
                $suggestedUser3
            ]);

        $this->cache->set("suggested-channels-offset::$userGuid", 3, 86400)
            ->shouldBeCalled();

        $this->getRecommendations([
            "limit" => $limit
        ])->shouldBeLike(new Response([
            (new Suggestion())
                ->setEntity($suggestedUser1)
                ->setEntityType('user'),
            (new Suggestion())
                ->setEntity($suggestedUser2)
                ->setEntityType('user'),
            (new Suggestion())
                ->setEntity($suggestedUser3)
                ->setEntityType('user'),
        ]));
    }

    public function it_should_get_recommendations_with_option_to_export_counts(
        User $user
    ): void {
        $suggestedUser1 = new User();
        $suggestedUser2 = new User();
        $suggestedUser3 = new User();

        $suggestedUser1CountExported = clone $suggestedUser1;
        $suggestedUser1CountExported->exportCounts = true;

        $suggestedUser2CountExported = clone $suggestedUser2;
        $suggestedUser2CountExported->exportCounts = true;

        $suggestedUser3CountExported = clone $suggestedUser3;
        $suggestedUser3CountExported->exportCounts = true;

        $limit = 3;
        $userGuid = '1234567890123451';

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->setUser($user);

        $this->cache->get("suggested-channels-offset::$userGuid", 0)
            ->shouldBeCalled()
            ->willReturn(0);

        $this->repository->getSubscriptionsOfSubscriptions(
            userGuid: $userGuid,
            limit: $limit,
            offset: 0,
        )->shouldBeCalled()
            ->willYield([
                $suggestedUser1,
                $suggestedUser2,
                $suggestedUser3
            ]);

        $this->cache->set("suggested-channels-offset::$userGuid", 3, 86400)
            ->shouldBeCalled();

        $this->getRecommendations([
            "limit" => $limit,
            "export_counts" => true
        ])->shouldBeLike(new Response([
            (new Suggestion())
                ->setEntity($suggestedUser1CountExported)
                ->setEntityType('user'),
            (new Suggestion())
                ->setEntity($suggestedUser2CountExported)
                ->setEntityType('user'),
            (new Suggestion())
                ->setEntity($suggestedUser3CountExported)
                ->setEntityType('user'),
        ]));
    }
}
