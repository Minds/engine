<?php
namespace Minds\Core\Data\cache;

use Psr\SimpleCache\CacheInterface;

/**
 * This cache is persitent for each worker
 * RoadRunner (start.rr.php) will keep this cache for each request, but it
 * will not share this cache between other workers
 */
class WorkerCache extends InMemoryCache
{
}
