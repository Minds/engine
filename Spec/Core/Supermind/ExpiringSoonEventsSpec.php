<?php

namespace Spec\Minds\Core\Supermind;

use Minds\Core\Data\cache\Cassandra as CassandraCache;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Supermind\Delegates\EventsDelegate;
use Minds\Core\Supermind\ExpiringSoonEvents;
use Minds\Core\Supermind\Repository;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ExpiringSoonEventsSpec extends ObjectBehavior
{
    /** @var CassandraCache */
    private $cache;

    /** @var EventsDelegate */
    private $eventsDelegate;

    /** @var Repository */
    private $repository;

    public function let(CassandraCache $cache, EventsDelegate $eventsDelegate, Repository $repository)
    {
        $this->beConstructedWith($cache, $eventsDelegate);

        $this->cache = $cache;
        $this->eventsDelegate = $eventsDelegate;
        $this->repository = $repository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ExpiringSoonEvents::class);
    }

    public function it_triggers_expiring_events(CassandraCache $cache, EventsDelegate $eventsDelegate, Repository $repository)
    {
        $cacheKey = 'supermind_expiring_soon_last_max_created_time';
        $earliestCreatedTime = 604800; // 7 days ago
        $latestCreatedTime = 518400; // 6 days ago

        $cachedTime = 561600; // 6.5 days ago

        $supermindRequest = new SupermindRequest();

        $supermindRequest
            ->setGuid('123')
            ->setReceiverGuid('456')
            ->setSenderGuid('789')
            ->setCreatedAt(518401)
            ->setStatus(1);

        $requests = [$supermindRequest];

        $this->beConstructedWith($cache, $eventsDelegate, $repository);

        $this->cache->has($cacheKey)
            ->willReturn(true);

        $this->cache->get($cacheKey)
            ->willReturn($cachedTime);

        // Created 7 days ago
        $this->getEarliestCreatedTime()
            ->willReturn($earliestCreatedTime);

        // Created 6 days ago
        $this->getLatestCreatedTime()
             ->willReturn($latestCreatedTime);

        $this->repository->getRequestsExpiringSoon($earliestCreatedTime, $latestCreatedTime)
            ->shouldBeCalled()
            ->willReturn(
                $requests
            );

        $this->eventsDelegate->onSupermindRequestExpiringSoon($requests[0]);

        $this->cache->set($cacheKey, $latestCreatedTime)
            ->shouldBeCalled();

        $this->triggerExpiringSoonEvents();
    }
}
