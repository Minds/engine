<?php
namespace Minds\Core\Search\MetricsSync;

use Minds\Traits\MagicAttributes;

/**
 * Class MetricsSync
 * @package Minds\Core\Feeds\Elastic
 * @method int|string getGuid()
 * @method MetricsSync setGuid(int|string $guid)
 * @method string getType()
 * @method MetricsSync setType(string $type)
 * @method string getMetric()
 * @method MetricsSync setMetric(string $metric)
 * @method int getCount()
 * @method MetricsSync setCount(int $count)
 * @method string getPeriod()
 * @method MetricsSync setPeriod(string $period)
 * @method int getSynced()
 * @method MetricsSync setSynced(int $synced)
 */
class MetricsSync
{
    use MagicAttributes;

    /** @var int|string */
    protected $guid;

    /** @var string */
    protected $type;

    /** @var string */
    protected $metric;

    /** @var int */
    protected $count;

    /** @var string */
    protected $period;

    /** @var int */
    protected $synced;
}
