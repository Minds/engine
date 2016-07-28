<?php
namespace Minds\Core\Analytics\Metrics;

use Minds\Helpers;
use Minds\Core;
use Minds\Core\Analytics\Timestamps;
use Minds\Interfaces\AnalyticsMetric;

/**
 * Signup Metrics
 */
class Signup implements AnalyticsMetric
{
    private $db;
    private $namespace = "analytics:signup";
    private $key;

    public function __construct($db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = new Core\Data\Call('entities_by_time');
        }

        if (Core\Session::getLoggedinUser()) {
            $this->key = Core\Session::getLoggedinUser()->guid;
        }
    }

    /**
     * Sets the current namespace
     * @param string $namesapce
     */
    public function setNamespace($namesapce)
    {
        //$this->namespace = $namespace . ":";
    }

    /**
     * Sets the current key
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Increments metric counter
     * @return bool
     */
    public function increment()
    {
        foreach (Timestamps::get(['day', 'month']) as $p => $ts) {
            $this->db->insert("{$this->namespace}:$p:$ts", [$this->key => time()]);
        }
        return true;
    }

    /**
     * Return a set of analytics for a timespan
     * @param  int    $span - eg. 3 (will return 3 units, eg 3 day, 3 months)
     * @param  string $unit - eg. day, month, year
     * @param  int    $timestamp (optional) - sets the base to work off
     * @return array
     */
    public function get($span = 3, $unit = 'day', $timestamp = null)
    {
        $timestamps = Timestamps::span($span, $unit);
        $data = [];
        foreach ($timestamps as $ts) {
            $data[] = [
                'timestamp' => $ts,
                'date' => date('d-m-Y', $ts),
                'total' => $this->db->countRow("{$this->namespace}:$unit:$ts")
            ];
        }
        return $data;
    }

    /**
     * Returns total metric counter
     * @return int
     */
    public function total()
    {
        return 0;
    }
}
