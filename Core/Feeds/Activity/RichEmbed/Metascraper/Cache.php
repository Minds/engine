<?php

namespace Minds\Core\Feeds\Activity\RichEmbed\Metascraper;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;

/**
 * Cache to avoid duplicating requests for Metascraper Server.
 */
class Cache
{
    // 1 day TTL.
    const TTL_SECONDS = 86400;

    /**
     * Constructor.
     * @param ?PsrWrapper $cache - PsrWrapper around cache.
     */
    public function __construct(
        private ?PsrWrapper $cache = null
    ) {
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Gets from cache by key, as pre-exported Metadata.
     * @param string $key - the key to get for (URL).
     * @return array|null - pre-exported data.
     */
    public function getExported(string $key): ?array
    {
        $cachedMetadata = $this->cache->get($key);
        return json_decode($cachedMetadata, true) ?? null;
    }

    /**
     * Set serialized, pre-exported metadata in cache.
     * @param string $key - key to cache by (URL).
     * @param Metadata $metadata - metadata to cache.
     * @return self
     */
    public function set(string $key, Metadata $metadata): self
    {
        $this->cache->set($key, json_encode($metadata), self::TTL_SECONDS);
        return $this;
    }
}
