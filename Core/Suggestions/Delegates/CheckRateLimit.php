<?php

namespace Minds\Core\Suggestions\Delegates;

use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Di\Di;
use Minds\Core\Security\RateLimits\Maps;

class CheckRateLimit
{
    /** @var abstractCacher */
    private $cacher;

    /** @var array */
    private $maps;

    const SUBSCRIBE_KEY = 'interaction:subscribe';

    public function __construct($cacher = null, $maps = null)
    {
        $this->cacher = $cacher ?: Di::_()->get('Cache');
        $this->maps = $maps ?: Maps::$maps;
    }

    /**
     * @param string|int $userGuid
     * @return bool false if about to get rate limited
     * @throws \Exception
     */
    public function check($userGuid)
    {
        if (!$userGuid) {
            throw new \Exception('userGuid must be provided');
        }
        $threshold = $this->maps[static::SUBSCRIBE_KEY]['threshold'];

        $cached = $this->cacher->get("subscriptions:user:$userGuid");

        if ($cached != false && $cached >= $threshold - 10) {
            return false;
        }

        return true;
    }

    /**
     * @param string|int $userGuid
     * @throws \Exception
     */
    public function incrementCache($userGuid)
    {
        if (!$userGuid) {
            throw new \Exception('userGuid must be provided');
        }
        $count = $this->cacher->get("subscriptions:user:$userGuid");

        if (!$count) {
            $count = 0;
        }

        $this->cacher->set("subscriptions:user:$userGuid", ++$count, $this->maps[static::SUBSCRIBE_KEY]['period']);
    }
}
