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
    private $cacheMock;

    /** @var EventsDelegate */
    private $eventsDelegateMock;

    /** @var Repository */
    private $repositoryMock;

    /** @var int */
    private $specTestEpoch = 1664453797;

    public function let(CassandraCache $cache, EventsDelegate $eventsDelegate, Repository $repository)
    {
        $this->beConstructedWith($cache, $eventsDelegate, $repository, $this->specTestEpoch);

        $this->cacheMock = $cache;
        $this->eventsDelegateMock = $eventsDelegate;
        $this->repositoryMock = $repository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ExpiringSoonEvents::class);
    }

    public function it_triggers_expiring_events_with_cache()
    {
        $cacheKey = 'supermind_expiring_soon_last_max_created_time';
        $earliestCreatedTime = $this->specTestEpoch - $this->daysToSeconds(7); // 7 days ago
        $latestCreatedTime = $this->specTestEpoch - $this->daysToSeconds(6); // 6 days ago

        $cachedTime = $this->specTestEpoch - $this->daysToSeconds(6.5); // 6.5 days ago

        $supermindRequest = new SupermindRequest();

        $supermindRequest
            ->setGuid('123')
            ->setReceiverGuid('456')
            ->setSenderGuid('789')
            ->setCreatedAt($this->specTestEpoch - ($this->daysToSeconds(6) + 1))
            ->setStatus(1);

        $requests = [$supermindRequest];

        $this->cacheMock->has($cacheKey)
            ->willReturn(true);

        $this->cacheMock->get($cacheKey)
            ->willReturn($cachedTime);
        
        $this->repositoryMock->getRequestsExpiringSoon($cachedTime, $latestCreatedTime)
            ->shouldBeCalled()
            ->willYield(
                $requests
            );

        $this->eventsDelegateMock->onSupermindRequestExpiringSoon($requests[0]);

        $this->cacheMock->set($cacheKey, $latestCreatedTime)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->triggerExpiringSoonEvents();
    }

    public function it_triggers_expiring_events_without_cache()
    {
        $cacheKey = 'supermind_expiring_soon_last_max_created_time';
        $earliestCreatedTime = $this->specTestEpoch - $this->daysToSeconds(7); // 7 days ago
        $latestCreatedTime = $this->specTestEpoch - $this->daysToSeconds(6); // 6 days ago

        $supermindRequest = new SupermindRequest();

        $supermindRequest
            ->setGuid('123')
            ->setReceiverGuid('456')
            ->setSenderGuid('789')
            ->setCreatedAt($this->specTestEpoch - ($this->daysToSeconds(6) + 1))
            ->setStatus(1);

        $requests = [$supermindRequest];

        $this->cacheMock->has($cacheKey)
            ->willReturn(false);

        $this->repositoryMock->getRequestsExpiringSoon($earliestCreatedTime, $latestCreatedTime)
            ->shouldBeCalled()
            ->willYield(
                $requests
            );

        $this->eventsDelegateMock->onSupermindRequestExpiringSoon($requests[0]);

        $this->cacheMock->set($cacheKey, $latestCreatedTime)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->triggerExpiringSoonEvents();
    }

    private function daysToSeconds(int $days): int
    {
        $dayInSecs = 86400;
        return $dayInSecs * $days;
    }
}
