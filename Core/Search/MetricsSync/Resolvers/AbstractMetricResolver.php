<?php
namespace Minds\Core\Search\MetricsSync\Resolvers;

use Minds\Core\Search\MetricsSync\MetricsSync;
use Minds\Core\Data\Call;

abstract class AbstractMetricResolver implements MetricResolverInterface
{
    /** @var Call */
    protected $db;

    /** @var string */
    protected $type;

    /** @var string */
    protected $subtype;

    /** @var int */
    protected $from;

    /** @var int */
    protected $to;

    /** @var Aggegates\Aggregate */
    protected $aggregator;

    /** @var string */
    protected $metricId;

    public function __construct($db = null)
    {
        $this->db = $db ?? new Call('entities_by_time');
    }

    /**
     * Set the type
     * @param string $type
     * @return MetricResolverInterface
     */
    public function setType(string $type): MetricResolverInterface
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set the subtype
     * @param string $subtype
     * @return MetricResolverInterface
     */
    public function setSubtype(string $subtype): MetricResolverInterface
    {
        $this->subtype = $subtype;
        return $this;
    }
    
    /**
     * Set min timestamp
     * @param int $from
     * @return MetricResolverInterface
     */
    public function setFrom(int $from): MetricResolverInterface
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Set max timestamp
     * @param int $to
     * @return MetricResolverInterface
     */
    public function setTo(int $to): MetricResolverInterface
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Return metrics
     * @return MetricsSync[]
     */
    public function get(): iterable
    {
        $this->aggregator
            ->setLimit(10000)
            ->setType($this->type)
            ->setSubtype($this->subtype)
            ->setFrom($this->from)
            ->setTo($this->to);

        $type = $this->type;

        if ($this->subtype) {
            $type = implode(':', [$this->type, $this->subtype]);
        }

        foreach ($this->aggregator->get() as $guid => $uniqueCountValue) {
            $count = $this->getTotalCount($guid);

            $metricsSync = new MetricsSync();
            $metricsSync
                ->setGuid($guid)
                ->setType($type)
                ->setMetric($this->metricId)
                ->setCount($count)
                ->setSynced(time() * 1000);

            yield $metricsSync;

            foreach ($this->getActivityGuids($metricsSync) as $guid) {
                $activityMetricsSync = clone $metricsSync;
                $activityMetricsSync->setType('activity')
                    ->setGuid($guid);
                yield $activityMetricsSync;
            }
        }
    }

    /**
     * Return the activity guids
     */
    protected function getActivityGuids(MetricsSync $metricsSync): array
    {
        if ($metricsSync->getType() === 'activity') {
            return [];
        }
        $guids = $this->db->getRow("activity:entitylink:{$metricsSync->getGuid()}");
        return array_values($guids);
    }

    /**
     * Return the total count
     * @param string $guid
     * @return int
     */
    abstract protected function getTotalCount(string $guid): int;
}
