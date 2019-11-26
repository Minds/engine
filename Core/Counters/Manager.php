<?php
/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Counters;

use Exception;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Di\Di;
use Minds\Helpers\Counters;

class Manager
{
    /** @var CassandraClient */
    protected $dbClient;

    /** @var int|string */
    protected $entityGuid = null;

    /** @var string */
    protected $metric;

    /**
     * Manager constructor.
     * @param CassandraClient $dbClient
     */
    public function __construct(
        $dbClient = null
    ) {
        $this->dbClient = $dbClient ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param int|string $entityGuid
     * @return Manager
     */
    public function setEntityGuid($entityGuid)
    {
        $this->entityGuid = $entityGuid;
        return $this;
    }

    /**
     * @param string $metric
     * @return Manager
     */
    public function setMetric(string $metric)
    {
        $this->metric = $metric;
        return $this;
    }

    /**
     * @param int $value
     * @return bool
     * @throws Exception
     */
    public function increment($value = 1)
    {
        if ($this->entityGuid === null) {
            throw new Exception('Invalid counter entity');
        }

        if (!$this->metric) {
            throw new Exception('Invalid counter metric');
        }

        Counters::increment($this->entityGuid, $this->metric, $value, $this->dbClient);
        return true;
    }

    /**
     * @param int $value
     * @return bool
     * @throws Exception
     */
    public function decrement($value = 1)
    {
        if ($this->entityGuid === null) {
            throw new Exception('Invalid counter entity');
        }

        if (!$this->metric) {
            throw new Exception('Invalid counter metric');
        }

        Counters::decrement($this->entityGuid, $this->metric, $value, $this->dbClient);
        return true;
    }

    /**
     * @param bool $cache
     * @return int
     * @throws Exception
     */
    public function get($cache = true)
    {
        if ($this->entityGuid === null) {
            throw new Exception('Invalid counter entity');
        }

        if (!$this->metric) {
            throw new Exception('Invalid counter metric');
        }

        return Counters::get($this->entityGuid, $this->metric, $cache, $this->dbClient);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function clear()
    {
        if ($this->entityGuid === null) {
            throw new Exception('Invalid counter entity');
        }

        if (!$this->metric) {
            throw new Exception('Invalid counter metric');
        }

        Counters::clear($this->entityGuid, $this->metric, 0, $this->dbClient);
        return true;
    }
}
