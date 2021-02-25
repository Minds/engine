<?php
namespace Minds\Core\Blockchain\Metrics;

use Minds\Common\Repository\AbstractRepositoryOpts;

/**
 * @method self setMetricId(string $metricId)
 * @method string getMetricId()
 * @method self setDateTs(int $unixTs)
 * @method int getDateTs()
 */
class MetricsQueryOpts extends AbstractRepositoryOpts
{
    /** @var string */
    protected $metricId;

    /** @var int */
    protected $timestamp = 0;
}
