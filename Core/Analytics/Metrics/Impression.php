<?php
/**
 * Impression Metric
 */
namespace Minds\Core\Analytics\Metrics;

use Minds\Helpers;
use Minds\Core\Analytics\Timestamps;
use Minds\Interfaces\AnalyticsMetric;

/**
 * Impression Metric
 */
class Impression implements AnalyticsMetric
{
    private $namespace = "";
    private $key;

    public function __construct()
    {
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
        Helpers\Counters::increment($this->key, "{$this->namespace}impression");
        foreach (Timestamps::get(['day', 'month']) as $p => $ts) {
            Helpers\Counters::increment($this->key, "{$this->namespace}impression:$p:$ts");
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
                'total' => Helpers\Counters::get($this->key, "{$this->namespace}impression:$unit:$ts")
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
        return Helpers\Counters::get($this->key, "{$this->namespace}impression");
    }
}
