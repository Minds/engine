<?php
namespace Minds\Core\Supermind;

use Minds\Core\Data\cache\Cassandra as CassandraCache;
use Minds\Core\Di\Di;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\Delegates\EventsDelegate;
use Minds\Core\Supermind\Repository;

class ExpiringSoonEvents
{
    /** @var CassandraCache */
    private $cache;

    /** @var EventsDelegate */
    private $eventsDelegate;

    /** @var Repository */
    private $repository;

    public function __construct(CassandraCache $cache = null, EventsDelegate $eventsDelegate = null, Repository $repository = null)
    {
        $this->cache = $cache ?? Di::_()->get('Cache\Cassandra');
        $this->eventsDelegate ??= new EventsDelegate();
        $this->repository ??= new Repository();
    }

    /**
     * Trigger events for all supermind requests that are expiring soon
     * (and haven't been triggered already)
     * @return bool
     */
    public function triggerExpiringSoonEvents(): bool
    {
        // We want the requests created between these times
        $earliestCreatedTime = $this->getEarliestCreatedTime();
        $latestCreatedTime = $this->getLatestCreatedTime();

        $requestsExpiringSoon = $this->repository->getRequestsExpiringSoon($earliestCreatedTime, $latestCreatedTime);

        foreach ($requestsExpiringSoon as $request) {
            $this->eventsDelegate->onSupermindRequestExpiringSoon($request);
        }

        // Store the most recent created time we just checked
        // So next time we run this function,
        // we don't re-trigger anything earlier than that
        $this->cache->set('supermind_expiring_soon_last_max_created_time', $latestCreatedTime);

        return true;
    }

    /**
     * Created time should be more than 6 days ago
     * @return int
     */
    private function getLatestCreatedTime(): int
    {
        // A supermind request expires after 7 days
        $lifespan = SupermindRequest::SUPERMIND_EXPIRY_THRESHOLD;

        // 1 day before expiry
        $soon = SupermindRequest::SUPERMIND_EXPIRING_SOON_THRESHOLD;

        // 6 days
        $lifespanBeforeExpiringSoon = $lifespan - $soon;

        // 6 days ago
        $latestCreatedTime = time() - $lifespanBeforeExpiringSoon;

        return $latestCreatedTime;
    }

    /**
     * We want requests created no more than 7 days ago
     * (as those requests have already expired)
     * and also created after the requests we've already checked
     * @return int
     */
    private function getEarliestCreatedTime(): int
    {
        // 7 days
        $lifespan = SupermindRequest::SUPERMIND_EXPIRY_THRESHOLD;

        // 7 days ago
        $earliestCreatedTime = time() - $lifespan;

        if ($this->cache->has('supermind_expiring_soon_last_max_created_time')) {
            // for example, 6.5 days ago
            $alreadyTriggeredRequestsOlderThan = $this->cache->get('supermind_expiring_soon_last_max_created_time');

            // e.g. Since we already triggered requests created 6.5+ days ago,
            // we no longer want to get requests between 6.5 - 7 days old.
            //
            // The oldest create date we DO want is younger
            // (aka a higher value create date) than the ones we've already triggered
            $earliestCreatedTime = max($earliestCreatedTime, $alreadyTriggeredRequestsOlderThan);
        }

        return $earliestCreatedTime;
    }
}
