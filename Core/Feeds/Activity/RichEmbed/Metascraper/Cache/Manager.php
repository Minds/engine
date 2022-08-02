<?php

namespace Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache;

use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Metadata;

/**
 * Cache to avoid duplicating requests for Metascraper Server.
 */
class Manager
{
    /**
     * Constructor.
     * @param Repository|null $repository
     */
    public function __construct(
        private ?Repository $repository = null,
    ) {
        $this->repository ??= new Repository();
    }

    /**
     * Gets from cache by key, as pre-exported Metadata.
     * @param string $key - the key to get for (URL).
     * @return array|null - pre-exported data.
     */
    public function getExported(string $key): ?array
    {
        $data = $this->repository->get($key);

        if (!$data) {
            return null;
        }

        return json_decode($data['data'], true) ?? null;
    }

    /**
     * Set serialized, pre-exported metadata in cache.
     * @param string $key - key to cache by (URL).
     * @param Metadata $metadata - metadata to cache.
     * @return self
     */
    public function set(string $key, Metadata $metadata): self
    {
        $this->repository->upsert($key, $metadata);
        return $this;
    }

    /**
     * Delete an entry from the cache.
     * @param string $key - url to delete by.
     * @return self
     */
    public function delete(string $url): self
    {
        $this->repository->delete($url);
        return $this;
    }
}
