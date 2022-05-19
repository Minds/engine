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
 *
 */
class Cassandra implements CacheInterface
{
    public function __construct(
        private ?CassandraClient $cassandraClient = null,
        private ?Logger $logger = null
    ) {
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Cql');
        $this->logger ??= Di::_()->get("Logger");
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function get($key, $default = null): ?array
    {
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

        return $response->first()['data'];
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function set($key, $value, $ttl = null)
    {
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
                    $key,
                    $value,
                    $ttl
                ]
            );

        $response = $this->cassandraClient->request($query);

        if (!$response) {
            throw new Exception("An error occurred while storing the 2factor code.");
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function delete($key)
    {
        $query = (new PreparedStatement())
            ->query(
                "DELETE FROM cache WHERE key = ?",
                [$key]
            );

        $response = $this->cassandraClient->request($query);

        if (!$response) {
            throw new Exception("An error occurred while deleting stored 2factor code.");
        }
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
     * @inheritDoc
     */
    public function has($key): bool
    {
        return $this->get($key) !== null;
    }
}
