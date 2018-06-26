<?php
/**
 * Class used to communicate with the cassandra database
 *
 */

namespace Minds\Core\Data;

use phpcassa\ColumnFamily;
use phpcassa\ColumnSlice;
use phpcassa\Connection\ConnectionPool;
use phpcassa\SystemManager;
use phpcassa\Schema\StrategyClass;
use phpcassa\Index\IndexClause;
use phpcassa\Index\IndexExpression;
use phpcassa\Schema\DataType\LongType;
use phpcassa\UUID;
use Minds\Core;
use Minds\Core\config;

class Call
{
    public static $keys = array();
    public static $reads = 0;
    public static $writes = 0;
    public static $deletes = 0;
    public static $counts = 0;
    private $pool;
    private $cf;
    private $client;

    public function __construct(
        $cf = null,
        $keyspace = null,
        $servers = null,
        $sendTimeout = 800,
        $receiveTimeout = 2000,
        $pool = null,
        $cql = null
    )
    {
        global $CONFIG;

        $this->servers = $servers ?: $CONFIG->cassandra->servers;
        $this->keyspace = $keyspace ?: $CONFIG->cassandra->keyspace;
        $this->cf_name = $cf;
        $this->client = $cql ?: Core\Di\Di::_()->get('Database\Cassandra\Cql');

        /*if ($this->keyspace != 'phpspec') {
            $this->ini();
        }*/
    }

    private function ini()
    {
        try {
            $this->pool = Pool::build($this->keyspace, $this->servers, 1, 2);
            if ($this->cf_name) {
                $this->cf = $this->getCf($this->cf_name);
            }
        } catch (\Exception $e) {
        }
    }

    public function describeKeyspace()
    {
        return $this->pool->describe_keyspace();
    }

    /**
     * Loads the cf interface
     * @param string $cf - the cf name
     * !!allowed cfs are 'site', 'session','plugin', 'object', 'user', 'user_index_to_guid', 'widget', 'entities_by_time',
     * 'notification', 'annotation', 'group', 'friends', 'friendsof', 'timeline','newsfeed','token'!!
     * @return the cassandra column family interface
     */
    public function getCf($cf)
    {
        return new ColumnFamily($this->pool, $cf);
    }

    public function insert($guid = null, array $data = array(), $ttl = null, $silent = false)
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

    public function insertBatch($rows = array())
    {
        return $this->cf->batch_insert($rows);
    }

    /**
     * Performs a standard get. NOTE - this will not return ordered content.
     *
     * @param array $options - a mixed array
     * @return array - raw data
     */
    public function get($offset = "", $limit=10)
    {
        self::$reads++;
        return $this->cf->get_range($offset, "", $limit, new ColumnSlice('', '', 10000));
    }

    /**
     * Performs a query based on indexes. The indexes must be predefined and this function
     * will not return ordered content. It is recommended to store your own index and query from there
     *
     * This function is good, however, for doing batch processing based on an index value.
     *
     * @param $expressions - an array of expressions
     * @return array
     */
    public function getByIndex(array $expressions = array(), $offset = "", $limit = 10)
    {
        foreach ($expressions as $column => $value) {
            $index_exps[] = new IndexExpression($column, $value);
        }
        $index_clause = new IndexClause($index_exps, $offset, $limit);
        return $this->cf->get_indexed_slices($index_clause);
    }

    /**
     * Performs a get request for a keys, to be used when an ID is known
     *
     * @param int/string $key - the key (row)
     * @param array $options - by default contains offset and limit for the row
     */
     public function getRow($key, array $options = array())
     {
         self::$reads++;
         array_push(self::$keys, $key);

         $options = array_merge(
             [
             'multi' => false,
             'offset' => "",
             'finish' => "",
             'limit' => 500,
             'reversed' => true
            ], $options);

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
         foreach ($result as $row){
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
    public function getRows($keys, array $options = array())
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
            if ($result = $future->get()) {
                $object = [];
                foreach ($result as $row){
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
            return (int) $result[0]['count'];
        } catch (Exception $e) {
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
        return $return;
    }

    /**
     * Removes attributes (columns) from a row
     * @param int/string $key - the key
     * @param array $attributes - the attributes to remove (columns)
     * @param bool $verify - return a count of true or false? (disable if doing batches as this can slow down)
     * @return mixed
     */
    public function removeAttributes($key, array $attributes = array(), $verify = false)
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
     * Create a column family
     *
     * @param string $name - the name of the column family
     * @param array $indexes - an array of indexes
     * @param array $attrs - any specific attributes for the column family to have
     */
    public function createCF($name, array $indexes = array(), array $attrs = array())
    {
        global $CONFIG;

        try {
            $sys = new SystemManager($this->servers[0]);

            $defaults = array(    "comparator_type" => "UTF8Type",
                "key_validation_class" => 'UTF8Type',
                "default_validation_class" => 'UTF8Type'
                );
            $attrs = array_merge($defaults, $attrs);

            $sys->create_column_family($this->keyspace, $name, $attrs);

            foreach ($indexes as $index => $data_type) {
                $sys->create_index($this->keyspace, $name, $index, $data_type);
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove a CF
     *
     * !DANGEROUS!
     */
    public function removeCF()
    {
        $sys = new SystemManager($this->servers[0]);
        return (bool) $sys->drop_column_family($this->keyspace, $this->cf_name);
    }

    /**
     * Does the keyspace exits
     *
     * @return bool
     */
    public function keyspaceExists()
    {
        $exists = false;
        try {
            $ks = $this->pool->describe_keyspace();
            $exists = false;
            foreach ($ks->cf_defs as $cfdef) {
                if ($cfdef->name == 'entities_by_time') {
                    $exists = true;
                    break;
                }
            }
        } catch (\Exception $e) {
            $exists = false;
        }
        return $exists;
    }

    /**
     * Create a keyspace
     *
     * @return bool
     */
    public function createKeyspace(array $attrs = array())
    {
        $sys = new SystemManager($this->servers[0]);
        $keyspace = $sys->create_keyspace($this->keyspace, $attrs);

        self::__construct(null, $this->keyspace, $this->servers);

        return (bool) $keyspace;
    }

    /**
     * Drop keyspace
     *
     * !DANGEROUS... extremely...!
     * @param bool $confirm - set to true to double check...
     *
     * @return void
     */
    public function dropKeyspace($confirm = false)
    {
        if (!$confirm) {
            return;
        }
        try {
            $sys = new SystemManager($this->servers[0]);
            $sys->drop_keyspace($this->keyspace);
        } catch (\Exception $e) {
            //var_dump($e); exit;
            return;
        }
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
    public function createObject(array $array = array())
    {
        $obj = new \stdClass;

        foreach ($array as $k=>$v) {
            $obj->$k = $v;
        }

        return $obj;
    }

    public function stats()
    {
        return $this->pool->stats();
    }
}
