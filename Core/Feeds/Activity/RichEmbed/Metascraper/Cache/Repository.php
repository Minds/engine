<?php

namespace Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache;

use Cassandra\Rows;
use Cassandra\Timestamp;
use Minds\Core\Config\Config;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Prepared\Custom as CustomQuery;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Metadata;

/**
 * Repository / access layer for Metascraper cache.
 */
class Repository
{
    // ttl for entries in the cache - defaults to 1 week.
    private $ttlSeconds = 604800;

    /**
     * Constructor.
     * @param Client|null $db
     * @param Config|null $config
     */
    public function __construct(
        private ?Client $db = null,
        private ?Config $config = null
    ) {
        $this->db ??= Di::_()->get('Database\Cassandra\Cql');
        $this->config ??= Di::_()->get('Config');

        if ($ttlSeconds = $this->config->get('metascraper')['ttl_seconds'] ?? false) {
            $this->ttlSeconds = $ttlSeconds;
        }
    }

    /**
     * Get matching rows from cache.
     * @param string $url - url to act as the key, following an md5 hash.
     * @return array|null - (first) matching row.
     */
    public function get(string $url): ?array
    {
        $statement = "SELECT * FROM metascraper_cache WHERE url_md5_hash = ?";
        $values = [md5($url)];
        $query = new CustomQuery();
        $query->query($statement, $values);
        return $this->db->request($query)->first();
    }

    /**
     * Upsert a row into the db.
     * @param string $url - url that when md5 hashes matches the db key.
     * @param Metadata $data - metadata to store upsert into the cache.
     * @return bool
     */
    public function upsert(string $url, Metadata $data): bool
    {
        $statement = "INSERT INTO metascraper_cache (
            url_md5_hash,
            data,
            last_scrape
        ) VALUES (?, ?, ?) USING TTL ?";

        $values = [
            md5($url), // url
            json_encode($data), // data
            new Timestamp(time(), 0), // last_scrape
            $this->ttlSeconds // ttl
        ];

        $query = new CustomQuery();
        $query->query($statement, $values);
        return (bool) $this->db->request($query);
    }


    /**
     * Delete a row from the db.
     * @param string $url - url that when md5 hashes matches the db key.
     * @return bool
     */
    public function delete(string $url): bool
    {
        $statement = "DELETE FROM metascraper_cache WHERE url_md5_hash = ?";
        $values = [md5($url)];
        $query = new CustomQuery();
        $query->query($statement, $values);
        return (bool) $this->db->request($query);
    }
}
