<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Minds\Core\Data\cache\Redis;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\Entities;

class Manager implements ManagerInterface
{
    public function __construct(
        private ?Redis $redisClient = null,
        private ?User $loggedInUser = null
    ) {
        $this->redisClient = $this->redisClient ?? Di::_()->get("Cache\Redis");
    }

    public function getUnseenTopEntities(): Entities
    {

    }

    private function IsCacheAvailable(): boolean
    {
        $cacheKey = "";
        $this->redisClient->get()
    }
}
