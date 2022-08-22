<?php

namespace Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache;

use Cassandra\Timestamp;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Prepared\Custom as CustomQuery;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Metadata;

/**
 * Repository / access layer for Metascraper cache.
 */
class Repository
{
    /**
     * Constructor.
     * @param Client|null $db
     */
    public function __construct(
        private ?Client $db = null,
    ) {
        $this->db ??= Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Get matching rows from cache.
     * @param string $url - url to act as the key, following an md5 hash.
     * @return array|null - (first) matching row.
     */
    public function get(string $url): ?array
    {
        $statement = "SELECT * FROM metascraper_cache WHERE url_md5_hash = ?";
        $values = [$this->getKey($url)];
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
        ) VALUES (?, ?, ?)";

        $values = [
            $this->getKey($url), // url
            json_encode($data), // data
            new Timestamp(time(), 0) // last_scrape
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
        $values = [$this->getKey($url)];
        $query = new CustomQuery();
        $query->query($statement, $values);
        return (bool) $this->db->request($query);
    }

    /**
     * Get key.
     * @param string $url - url to get key for.
     * @return string key.
     */
    private function getKey(string $url): string
    {
        // trim any trailing slash.
        if (substr($url, -1) === '/') {
            $url = substr($url, 0, -1);
        }
        // return md5 hash for key.
        return md5($url);
    }
}
