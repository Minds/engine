<?php

namespace Spec\Minds\Core\Search\MetricsSync;

use Minds\Core\Search\MetricsSync\Manager;
use Minds\Core\Search\MetricsSync\MetricsSync;
use Minds\Core\Search\MetricsSync\Repository;
use Minds\Core\Search\MetricsSync\Resolvers;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    public function let(Repository $repository)
    {
        $this->beConstructedWith($repository);
        $this->repository = $repository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_synchronise_metrics(Resolvers\CommentsCountMetricResolver $commentsResolver)
    {
        $metric1 = (new MetricsSync());
        $metric2 = (new MetricsSync());

        $commentsResolver->setType('activity')
            ->shouldBeCalled()
            ->willReturn($commentsResolver);
        $commentsResolver->setSubtype('')
            ->shouldBeCalled()
            ->willReturn($commentsResolver);
        $commentsResolver->setFrom(0)
            ->shouldBeCalled()
            ->willReturn($commentsResolver);
        $commentsResolver->setTo(10)
            ->shouldBeCalled()
            ->willReturn($commentsResolver);
        $commentsResolver->get()
            ->shouldBeCalled()
            ->willReturn([
                $metric1,
                $metric2
            ]);

        $this->repository->add($metric1)
            ->shouldBeCalled();

        $this->repository->bulk()
            ->shouldBeCalled();

        $this->setType('activity')
            ->setFrom(0)
            ->setTo(10);
        $this->run([ $commentsResolver ]);
    }
}
