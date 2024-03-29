<?php
/**
 * Class used to communicate with the cassandra database
 *
 */

namespace Minds\Core\Data;

use Minds\Core;
use phpcassa\ColumnFamily;
use phpcassa\ColumnSlice;
use phpcassa\Connection\ConnectionPool;
use phpcassa\Index\IndexClause;
use phpcassa\Index\IndexExpression;
use phpcassa\Schema\DataType\LongType;
use phpcassa\Schema\StrategyClass;
use phpcassa\SystemManager;
use phpcassa\UUID;

class Call
{
    public static $keys = [];
    public static $reads = 0;
    public static $writes = 0;
    public static $deletes = 0;
    public static $counts = 0;
    private $cf;
    private $client;
    private $servers;
    private $keyspace;
    private $cf_name;

    private int $tenantId;

    public function __construct(
        $cf = null,
        $keyspace = null,
        $servers = null,
        $cql = null
    ) {
        // global $CONFIG;
        $config = Core\Di\Di::_()->get('Config');

        $this->servers = $servers ?: $config->get('cassandra')['servers'];
        $this->keyspace = $keyspace ?: $config->get('cassandra')['keyspace'];
        $this->cf_name = $cf;
        $this->client = $cql ?: Core\Di\Di::_()->get('Database\Cassandra\Cql');

        $this->tenantId = $config->get('tenant_id') ?: 0;
    }

    public function insert($guid = null, array $data = [], $ttl = null, $silent = false)
    {
        if (!$guid) {
            $guid = Core\Guid::build();
        }
        self::$writes++;
        //unset guid, we don't want it twice
        unset($data['guid']);

        $requests = [];

        foreach ($data as $column1 => $value) {
            $statement = "INSERT INTO $this->cf_name (key, column1, value) VALUES (?, ?, ?)";
            $values = [ (string) $guid, (string) $column1, (string) $value ];

            if ($ttl) {
                $statement .= " USING TTL ?";
                $values[] = $ttl;
            }

            $requests[] = [
                'string' => $statement,
                'values' => $values,
            ];
        }

        try {
            $this->client->batchRequest($requests, \Cassandra::BATCH_UNLOGGED, $silent);
        } catch (\Exception $e) {
            error_log(print_r($e, true));
            return false;
        }

        return $guid;
    }

    public function insertBatch($rows = [])
    {
        return $this->cf->batch_insert($rows);
    }


    /**
     * Performs a get request for a keys, to be used when an ID is known
     *
     * @param int/string $key - the key (row)
     * @param array $options - by default contains offset and limit for the row
     */
    public function getRow($key, array $options = [])
    {
        self::$reads++;
        array_push(self::$keys, $key);

        $options = array_merge(
            [
             'multi' => false,
             'offset' => "",
             'finish' => "",
             'limit' => 5000,
             'reversed' => true
            ],
            $options
        );

        $query = new Cassandra\Prepared\Custom();

        $statement = "SELECT * FROM";
        $values = [];

        $statement .= " $this->cf_name WHERE key=?";
        $values = [ (string) $key ];

        if ($options['offset']) {
            if ($options['reversed']) {
                $statement .= " AND column1 <= ? AND column1 >= ?";
                $values[] = (string) $options['offset'];
                $values[] = (string) $options['finish'];
            } else {
                $statement .= ' AND column1 >= ?';
                $values[] = (string) $options['offset'];
                if ($options['finish']) {
                    $statement .= ' AND column1 <= ?';
                    $values[] = (string) $options['finish'];
                }
            }
        }

        $statement .= $options['reversed'] ? " ORDER BY column1 DESC" : " ORDER BY column1 ASC";

        $query->setOpts([
             'page_size' => (int) $options['limit'],
         ]);
        $query->query($statement, $values);

        try {
            $result = $this->client->request($query);
        } catch (\Exception $e) {
            return null;
        }

        if (!$result) {
            return [];
        }

        $object = [];
        foreach ($result as $row) {
            $row = array_values($row);
            $object[$row[1]] = $row[2];
        }
        return $object;
    }

    /**
     * Performs a get requests for multiple keys
     *
     * @param int/string $key - the key (row)
     * @param array $options - by default contains offset and limit for the row
     */
    public function getRows($keys, array $options = [])
    {
        $objects = [];
        $requests = [];

        foreach ($keys as $key) {
            $statement = "SELECT * FROM $this->cf_name WHERE key=?";
            $values = [ (string) $key ];

            if (isset($options['offset'])) {
                $statement .= " AND column1 >= ?";
                $values[] = (string) $options['offset'];
            }

            if (isset($options['limit'])) {
                $statement .= " LIMIT ?";
                $values[] = (int) $options['limit'];
            }

            $query = new Cassandra\Prepared\Custom();
            $query->query($statement, $values);

            $requests[$key] = $this->client->request($query, true);
        }

        foreach ($requests as $key => $future) {
            if ($future && $result = $future->get()) {
                $object = [];
                foreach ($result as $row) {
                    $row = array_values($row);
                    $object[$row[1]] = $row[2];
                }
                if ($object) {
                    $objects[$key] = $object;
                }
            }
        }
        return $objects;
    }

    /**
     * Count the columns of a row
     */
    public function countRow($key)
    {
        //if (!$this->cf) {
        //    return 0;
        //}
        //return 10; //quick hack until wil figue this out!
        if (!$key) {
            return 0;
        }
        try {
            self::$counts++;
            $query = new Cassandra\Prepared\Custom();

            $statement = "SELECT count(*) as count FROM $this->cf_name WHERE key=?";
            $values = [ (string) $key ];
            $query->query($statement, $values);

            $result = $this->client->request($query);
            return (int) (isset($result[0]) ? $result[0]['count'] : 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Removes a row from a column family
     * @param int/string $key - the key
     * @return mixed
     */
    public function removeRow($key)
    {
        if (!$key) {
            return false;
        }
        self::$deletes++;

        $statement = "DELETE FROM $this->cf_name WHERE key=?";
        $values = [ (string) $key ];

        $query = new Cassandra\Prepared\Custom();
        $query->query($statement, $values);

        try {
            $this->client->request($query);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Removes multiple rows from a column family
     * @param array $keys - array of keys to delete
     * @return array
     */
    public function removeRows(array $keys)
    {
        foreach ($keys as $key) {
            $return[$key] = $this->removeRow($key);
        }
        return $return ?? null;
    }

    /**
     * Removes attributes (columns) from a row
     * @param int/string $key - the key
     * @param array $attributes - the attributes to remove (columns)
     * @param bool $verify - return a count of true or false? (disable if doing batches as this can slow down)
     * @return mixed
     */
    public function removeAttributes($key, array $attributes = [], $verify = false)
    {
        self::$deletes++;

        if (empty($attributes)) {
            return false; // don't allow as this will delete the row!
        }

        $requests = [];
        $statement = "DELETE FROM $this->cf_name WHERE key=? and column1 = ?";

        foreach ($attributes as $column1) {
            $values = [(string) $key, (string) $column1];
            $requests[] = [
                'string' => $statement,
                'values' => $values,
            ];
        }

        try {
            $this->client->batchRequest($requests, \Cassandra::BATCH_UNLOGGED);
        } catch (\Exception $e) {
            error_log(print_r($e, true));
            return false;
        }

        return true;
    }

    /**
     * Create and index for a column family
     *
     * NOTE: This function should be called by a plugin if it needs to query by 'metadata'.
     * You should favour a design in which you use your own indexing system though.
     */
    public function createIndex()
    {
    }

    /**
     * Create an object from an array
     *
     * @param array $array - the array
     * @return object $object - the object
     * @todo Make a DB specific object rather than stdClass.
     */
    public function createObject(array $array = [])
    {
        $obj = new \stdClass;

        foreach ($array as $k=>$v) {
            $obj->$k = $v;
        }

        return $obj;
    }
}
