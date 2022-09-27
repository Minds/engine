<?php
namespace Minds\Core\Supermind;

use Minds\Core\Data\cache\Cassandra as CassandraCache;
use Minds\Core\Di\Di;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\Delegates\EventsDelegate;

class ExpiringSoonEvents
{
    /** @var CassandraCache */
    protected $cache;

    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    public function __construct(CassandraCache $cache = null, private ?EventsDelegate $eventsDelegate = null)
    {
        $this->cache = $cache ?? Di::_()->get('Cache\Cassandra');
        $this->eventsDelegate ??= new EventsDelegate();
    }

    /**
     * @return bool
     * @throws ForbiddenException
     */
    public function triggerExpiringSoonEvents(): bool
    {
        // A supermind request expires after 7 days
        $lifespan = SupermindRequest::SUPERMIND_EXPIRY_THRESHOLD;

        // 1 day left before expiry
        $soon = SupermindRequest::SUPERMIND_EXPIRING_SOON_THRESHOLD;

        // 6 days is the age we want to start triggering soon events
        $lifespanBeforeExpiringSoon = $lifespan - $soon;


        // We want to get the requests that are between 6 and 7 days old
        // but only the ones that we haven't checked on previous runs


        // CreatedAt should be more than 6 days ago
        // and not older than the previous time we ran this check
        $gtTime = min(time() - $lifespanBeforeExpiringSoon, $this->cache->get('supermind_expiring_soon_last_from_time'));

        // CreatedAt should not be more than 7 days ago
        // (we don't want to notify requests already expired)
        $lteTime = time() - $lifespan;

        $requestsExpiringSoon = $this->repository->getRequestsExpiringSoon($gtTime, $lteTime);

        foreach ($requestsExpiringSoon as $request)
        {
            $this->eventsDelegate->onSupermindRequestExpiringSoon($request);
        }

        // Store the earliest created time we just checked
        $this->cache->set('supermind_expiring_soon_last_from_time', $gtTime);

        return true;
    }
}




