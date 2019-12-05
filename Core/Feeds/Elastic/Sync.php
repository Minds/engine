<?php
/**
 * Sync
 * @author edgebal
 */

namespace Minds\Core\Feeds\Elastic;

use Exception;
use Minds\Core\Counters;
use Minds\Core\Di\Di;
use Minds\Core\Trending\Aggregates;

class Sync
{
    /** @var string */
    protected $type;

    /** @var string */
    protected $subtype;

    /** @var int */
    protected $from;

    /** @var int */
    protected $to;

    /** @var string */
    protected $metric;

    /** @var Repository */
    protected $repository;

    /** @var Counters */
    protected $counters;

    /**
     * Sync constructor.
     * @param Repository $repository
     * @param Counters $counters
     */
    public function __construct(
        $repository = null,
        $counters = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->counters = $counters ?: Di::_()->get('Entities\Counters');
    }

    /**
     * @param string $type
     * @return Sync
     */
    public function setType(string $type): Sync
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $subtype
     * @return Sync
     */
    public function setSubtype(string $subtype): Sync
    {
        $this->subtype = $subtype;
        return $this;
    }

    /**
     * @param int $from
     * @return Sync
     */
    public function setFrom(int $from): Sync
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @param int $to
     * @return Sync
     */
    public function setTo(int $to): Sync
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @param string $metric
     * @return Sync
     */
    public function setMetric(string $metric): Sync
    {
        $this->metric = $metric;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        $type = $this->type;

        if ($this->subtype) {
            $type = implode(':', [$this->type, $this->subtype]);
        }

        switch ($this->metric) {
            case 'up':
                $metricMethod = 'getVotesUp';
                $metricId = 'votes:up';
                $cassandraCountersMetricId = 'thumbs:up';
                break;

            case 'down':
                $metricMethod = 'getVotesDown';
                $metricId = 'votes:down';
                $cassandraCountersMetricId = 'thumbs:down';
                break;

            default:
                throw new Exception('Invalid metric');
        }

        // Sync

        $i = 0;
        foreach ($this->{$metricMethod}($this->from, $this->to) as $guid => $uniqueCountValue) {
            try {
                $count = $this->counters->get($guid, $cassandraCountersMetricId);
            } catch (Exception $e) {
                error_log((string)$e);
                $count = (int) abs($uniqueCountValue ?: 0);
            }

            $metric = new MetricsSync();
            $metric
                ->setGuid($guid)
                ->setType($type)
                ->setMetric($metricId)
                ->setCount($count)
                ->setSynced(time());
            try {
                $this->repository->add($metric);
            } catch (Exception $e) {
                error_log((string)$e);
            }

            echo sprintf("\n#%s: %s -> %s = %s", ++$i, $guid, $metricId, $count);
        }

        // Clear any pending bulk inserts
        $this->repository->bulk();
    }

    /**
     * @param int $from
     * @param int $to
     * @return iterable
     */
    protected function getVotesUp(int $from, int $to): iterable
    {
        $aggregates = new Aggregates\Votes;
        $aggregates
            ->setLimit(10000)
            ->setType($this->type)
            ->setSubtype($this->subtype)
            ->setFrom($from)
            ->setTo($to);

        return $aggregates->get();
    }

    /**
     * @param int $from
     * @param int $to
     * @return iterable
     */
    protected function getVotesDown(int $from, int $to): iterable
    {
        $aggregates = new Aggregates\DownVotes;
        $aggregates
            ->setLimit(10000)
            ->setType($this->type)
            ->setSubtype($this->subtype)
            ->setFrom($from)
            ->setTo($to);

        return $aggregates->get();
    }
}
