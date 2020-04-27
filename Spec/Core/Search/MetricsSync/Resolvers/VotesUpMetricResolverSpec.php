<?php

namespace Spec\Minds\Core\Search\MetricsSync\Resolvers;

use Minds\Core\Search\MetricsSync\Resolvers\VotesUpMetricResolver;
use Minds\Core\Trending\Aggregates\Votes;
use Minds\Core\Counters;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class VotesUpMetricResolverSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(VotesUpMetricResolver::class);
    }

    public function it_should_return_metric_sync_iterable(Counters $counters, Votes $aggregator)
    {
        $this->beConstructedWith($counters, $aggregator);
        $counters->get('123', 'thumbs:up')
            ->shouldBeCalled()
            ->willReturn(10);
        $counters->get('456', 'thumbs:up')
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
