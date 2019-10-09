<?php

namespace Spec\Minds\Core\Feeds\Scheduled;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Scheduled\Manager;
use Minds\Core\Feeds\Scheduled\Repository;
use Minds\Core\Search\Search;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Search */
    protected $search;

    public function let(
        Repository $repository
    ) {
        $this->repository = $repository;
        $this->beConstructedWith($repository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_scheduled_count()
    {
        $argument = ['container_guid' => 9999, 'type' => 'activity'];
        $this->repository->getScheduledCount($argument)
            ->shouldBeCalled()
            ->willReturn(1);
        
        $this->getScheduledCount($argument);
    }
}
