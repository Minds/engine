<?php

namespace Minds\Core\Wire\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Wire\Counter;
use Minds\Core\Wire\Wire;

class CacheDelegate
{
    /** @var Data\cache\Redis */
    private $cache;

    public function __construct($cache = null)
    {
        $this->cache = $cache ?: Di::_()->get('Cache');
    }

    /**
     * OnAdd, clear the cache
     * @param Wire $wire
     * @return void
     */
    public function onAdd(Wire $wire): void
    {
        $this->cache->destroy(Counter::getIndexName($wire->getEntity()->guid, null, 'tokens', null, true));
    }
}
