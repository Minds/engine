<?php
namespace Minds\Core\Data\cache;

use Psr\SimpleCache\CacheInterface;

/**
 * This cache is will first user the WorkerCache and then fallback to the Redis Cache
 */
class SharedCache extends Redis
{
}
