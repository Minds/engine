<?php

namespace Spec\Minds\Core\Search\MetricsSync\Resolvers;

use Minds\Core\Search\MetricsSync\Resolvers\CommentsCountMetricResolver;
use Minds\Core\Trending\Aggregates\Comments;
use Minds\Core\Comments\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CommentsCountMetricResolverSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(CommentsCountMetricResolver::class);
    }

    public function it_should_return_metric_sync_iterable(Manager $commentsManager, Comments $aggregator)
    {
        $this->beConstructedWith($commentsManager, $aggregator);
        
        $commentsManager->count('123', null, true)
            ->shouldBeCalled()
            ->willReturn(10);
        $commentsManager->count('456', null, true)
            ->shouldBeCalled()
            ->willReturn(100);

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
                '123' => 10,
                '456' => 52,
            ]);

        $this->get()->shouldHaveCount(2);
    }
}
