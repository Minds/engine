<?php

namespace Spec\Minds\Core\Search\MetricsSync\Resolvers;

use Minds\Core\Search\MetricsSync\Resolvers\VotesUpMetricResolver;
use Minds\Core\Trending\Aggregates\Votes;
use Minds\Core\Counters;
use Minds\Core\Votes\Enums\VoteEnum;
use Minds\Core\Votes\MySqlRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class VotesUpMetricResolverSpec extends ObjectBehavior
{
    public function let(MySqlRepository $repository, Votes $aggregator)
    {
        $this->beConstructedWith($repository, $aggregator);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(VotesUpMetricResolver::class);
    }

    public function it_should_return_metric_sync_iterable(MySqlRepository $repository, Votes $aggregator)
    {
        $this->beConstructedWith($repository, $aggregator);
        $repository->getCount(123, VoteEnum::UP)
            ->shouldBeCalled()
            ->willReturn(10);
        $repository->getCount(456, VoteEnum::UP)
            ->shouldBeCalled()
            ->willReturn(1);

        $aggregator->setLimit(Argument::any())
            ->willReturn($aggregator);
        $aggregator->setType(Argument::any())
            ->willReturn($aggregator);
        $aggregator->setSubtype(Argument::any())
            ->willReturn($aggregator);
        $aggregator->setFrom(Argument::any())
            ->willReturn($aggregator);
        $aggregator->setTo(Argument::any())
            ->willReturn($aggregator);
        $aggregator->get()
            ->shouldBeCalled()
            ->willReturn([
                '123' => 1,
                '456' => 20
            ]);

        $this->get()->shouldHaveCount(2);
    }
}
