<?php

namespace Minds\Core\Data\cache;

use Exception;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom as PreparedStatement;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Exceptions\NotFoundException;
use NotImplementedException;
use Psr\SimpleCache\CacheInterface;

/**
 * Cassandra implementation of cache
 */
class Cassandra implements CacheInterface
{
    const IN_MEMORY_PREFIX = "cassandra:";

    public function __construct(
        private ?CassandraClient $cassandraClient = null,
        private ?Logger          $logger = null,
        private ?InMemoryCache $inMemoryCache = null,
    ) {
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Cql');
        $this->logger ??= Di::_()->get("Logger");
        $this->inMemoryCache ??= Di::_()->get(InMemoryCache::class);
    }

    /**
     * Get a key from the cache table
     * @inheritDoc
     * @throws Exception
     */
    public function get($key, $default = null)
    {
        if ($this->inMemoryCache->has(self::IN_MEMORY_PREFIX . $key)) {
            return $this->inMemoryCache->get(self::IN_MEMORY_PREFIX . $key);
        }

        $query = (new PreparedStatement())
            ->query(
                "SELECT *
                FROM
                    cache
                WHERE
                    key = ?",
                [$key]
            );

        $response = $this->cassandraClient->request($query);

        if (!$response) {
            throw new NotFoundException("No entries were found for the provided key");
        }

        if ($response->count() === 0) {
            return $default;
        }

        $value = $response->first()['data'];

        if ($value !== false) {
            $value = json_decode($value, true);

            $this->inMemoryCache->set(self::IN_MEMORY_PREFIX . $key, $value);

            return $value;
        }

        return $default;
    }

    /**
     * Set a key in the cache table
     * @inheritDoc
     * @throws Exception
     */
    public function set($key, $value, $ttl = null): bool
    {
        $this->inMemoryCache->set(self::IN_MEMORY_PREFIX . $key, $value);

        $value = json_encode($value);

        $query = (new PreparedStatement())
            ->query(
                "INSERT INTO
                cache
                (
                    key,
                    data
                )
                VALUES
                    (?, ?)
                USING TTL ?;",
                [
                    (string)$key,
                    (string)$value,
                    $ttl
                ]
            );

        $response = $this->cassandraClient->request($query);

        if (!$response) {
            throw new Exception("An error occurred while storing the 2factor code.");
        }

        return true;
    }

    /**
     * Removes a key from the cache table
     * @inheritDoc
     * @throws Exception
     */
    public function delete($key): bool
    {
        $this->inMemoryCache->delete(self::IN_MEMORY_PREFIX . $key);

        $query = (new PreparedStatement())
            ->query(
                "DELETE FROM cache WHERE key = ?",
                [$key]
            );

        $response = $this->cassandraClient->request($query);

        if (!$response) {
            throw new Exception("An error occurred while deleting stored 2factor code.");
        }

        return true;
    }

    /**
     * @inheritDoc
     * @throws NotImplementedException
     */
    public function clear(): bool
    {
        throw new NotImplementedException();
    }

    /**
     * Retrieves multiple keys at once from the cache table
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null): array
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->get($key);
        }

        return $results;
    }

    /**
     * Set multiple keys at once in the cache table
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * Delete multiple keys at once from the cache table
     * @inheritDoc
     */
    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * Check if a key exists within the cache table
     * @inheritDoc
     */
    public function has($key): bool
    {
        return $this->get($key) !== null;
    }
}
