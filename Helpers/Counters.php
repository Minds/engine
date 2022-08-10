<?php

namespace Minds\Helpers;

use Minds\Core;

/**
 * Helper for metric counters
 * @todo Avoid static and use proper DI (check $client at methods)
 */
class Counters
{
    /**
     * Increment a metric count
     * @param  Entity|number|string $entity
     * @param  string        $metric
     * @param  int           $value  - Value to increment. Defaults to 1.
     * @param  Data\Client   $client - Database. Defaults to Cassandra.
     * @return void
     */
    public static function increment($entity, $metric, $value = 1, $client = null)
    {
        if (is_numeric($entity) || is_string($entity)) {
            $guid = $entity;
        //error_log($guid);
        } else {
            if ($entity->guid) {
                $guid = $entity->guid;
            } else {
                return null;
            }
        }
        if (!$client) {
            $client = Core\Data\Client::build('Cassandra');
        }
        $query = new Core\Data\Cassandra\Prepared\Counters();
        try {
            $client->request($query->update($guid, $metric, $value), true);
        } catch (\Exception $e) {
        }

        // Rebuild cache
        self::get($entity, $metric, false, $client);
    }

    /**
     * Decrements a metric count
     * @param  Entity|number $entity
     * @param  string        $metric
     * @param  int           $value  - Value to increment. Defaults to 1.
     * @param  Data\Client   $client - Database. Defaults to Cassandra.
     * @return void
     */
    public static function decrement($entity, $metric, $value = 1, $client = null)
    {
        if (is_numeric($entity) || is_string($entity)) {
            $guid = $entity;
        } else {
            $guid = $entity->guid;
        }
        $value = $value * -1; //force negative
        try {
            if (!$client) {
                $client = Core\Data\Client::build('Cassandra');
            }
            $query = new Core\Data\Cassandra\Prepared\Counters();
            $client->request($query->update($guid, $metric, $value));

            // Rebuild cache
            self::get($entity, $metric, false, $client);
        } catch (\Exception $e) {
        }
    }

    /**
     * Returns the count for a single metric on an entity
     * @param  Entity|number|string  $entity
     * @param  string         $metric
     * @param  boolean        $cache  - use a cache for result?
     * @param  Data\Client    $client - Database. Defaults to Cassandra.
     * @return int
     */
    public static function get($entity, $metric, $cache = true, $client = null)
    {
        $cacher = Core\Data\cache\factory::build();
        if (is_numeric($entity) || is_string($entity)) {
            $guid = $entity;
        } else {
            $guid = $entity->guid;
        }
        $cached = $cache ? $cacher->get("counter:$guid:$metric") : false;
        if ($cached !== false) {
            return (int) $cached;
        }

        try {
            if (!$client) {
                $client = Core\Di\Di::_()->get('Database\Cassandra\Cql');
            }
            $query = new Core\Data\Cassandra\Prepared\Counters();
            $result = $client->request($query->get($guid, $metric));
            if (isset($result[0]) && isset($result[0]['count'])) {
                $count = (int) $result[0]['count'];
            } else {
                $count =  0;
            }
        } catch (\Exception $e) {
            return 0;
        }
        $cacher->set("counter:$guid:$metric", $count, 259200); //cache for 3 days
        return (int) $count;
    }

    /**
     * Resets a metric counter for an entity
     * @param  Entity|number  $entity
     * @param  string         $metric
     * @param  number         $value  - Resetted value. Defaults to 0.
     * @param  Data\Client    $client - Database. Defaults to Cassandra.
     */
    public static function clear($entity, $metric, $value = 0, $client = null)
    {
        if (is_numeric($entity) || is_string($entity)) {
            $guid = $entity;
        } else {
            if ($entity->guid) {
                $guid = $entity->guid;
            } else {
                return null;
            }
        }
        if (!$client) {
            $client = Core\Data\Client::build('Cassandra');
        }
        $query = new Core\Data\Cassandra\Prepared\Counters();
        //$client->request($query->clear($guid, $metric));
        $count = self::get($entity, $metric, false, $client);
        self::decrement($entity, $metric, $count, $client);
    }
}
