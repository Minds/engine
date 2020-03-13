<?php

namespace Spec\Minds\Core\Search\MetricsSync\Resolvers;

use Minds\Core\Search\MetricsSync\Resolvers\VotesDownMetricResolver;
use Minds\Core\Trending\Aggregates\DownVotes;
use Minds\Core\Counters;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class VotesDownMetricResolverSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(VotesDownMetricResolver::class);
    }

    public function it_should_return_metric_sync_iterable(Counters $counters, DownVotes $aggregator)
    {
        $this->beConstructedWith($counters, $aggregator);
        $counters->get('123', 'thumbs:down')
            ->shouldBeCalled()
            ->willReturn(10);
        $counters->get('456', 'thumbs:down')
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
