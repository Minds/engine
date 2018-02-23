<?php
namespace Minds\Core\Analytics;

use Minds\Core;
use Minds\Interfaces\AnalyticsMetric;

/**
 * User Factory for Analytics
 */
class User
{
    private static $_;

    private $metric;
    private $key;

    public function __construct()
    {
    }

    /**
     * Instanciates a new Metrics class
     * @param  string|AnalyticsMetric $metric - Metric class name or instance
     * @return $this
     */
    public function setMetric($metric)
    {
        if (is_string($metric)) {
            $metric_class = "Minds\\Core\\Analytics\\Metrics\\" . ucfirst($metric);
            if (class_exists($metric_class)) {
                $metric = new $metric_class();
            }
        }
        if ($metric instanceof AnalyticsMetric) {
            $this->metric = $metric;
        } else {
            throw new \Exception('AnalyticsMetric not provided');
        }
        $this->metric->setNamespace('user');
        $this->metric->setKey($this->key);
        return $this;
    }

    /**
     * Sets metric key
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
        if ($this->metric) {
            $this->metric->setKey($key);
        }
        return $this;
    }

    /**
     * Increments metric counter
     * @return mixed
     */
    public function increment()
    {
        return $this->metric->increment();
    }

    /**
     * Return a set of analytics for a timespan
     * @param int    $span - eg. 3 (will return 3 units, eg 3 day, 3 months)
     * @param string $unit - eg. day, month, year
     * @param int    $timestamp (optional) - sets the base to work off
     * @return array
     */
    public function get($span = 3, $unit = "day", $timestamp = null)
    {
        return $this->metric->get($span, $unit, $timestamp);
    }

    /**
     * Factory singleton builder
     * @return static
     */
    public static function _()
    {
        if (!self::$_) {
            self::$_ = new User();
        }
        return self::$_;
    }
}
