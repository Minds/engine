<?php
/**
 * Sync
 * @author edgebal
 */

namespace Minds\Core\Feeds\Elastic;

use Exception;
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

    /**
     * Sync constructor.
     * @param Repository $repository
     */
    public function __construct(
        $repository = null
    )
    {
        $this->repository = $repository ?: new Repository();
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
                $sign = 1;
                break;

            case 'down':
                $metricMethod = 'getVotesDown';
                $metricId = 'votes:down';
                $sign = -1;
                break;

            default:
                throw new Exception('Invalid metric');
        }

        // Sync

        $i = 0;
        foreach ($this->{$metricMethod}() as $guid => $count) {
            $countValue = $sign * $count;

            $metric = new MetricsSync();
            $metric
                ->setGuid($guid)
                ->setType($type)
                ->setMetric($metricId)
                ->setCount($countValue)
                ->setSynced(time());
            try {
                $this->repository->inc($metric);
            } catch (Exception $e) {
                error_log((string) $e);
            }

            echo sprintf("\n%s: %s -> %s = %s", ++$i, $guid, $metricId, $countValue);
        }

        // Clear any pending bulk inserts
        $this->repository->bulk();
    }


    /**
     * @return iterable
     */
    protected function getVotesUp(): iterable
    {
        $aggregates = new Aggregates\Votes;
        $aggregates->setLimit(10000);
        $aggregates->setType($this->type);
        $aggregates->setSubtype($this->subtype);
        $aggregates->setFrom($this->from);
        $aggregates->setTo($this->to);

        return $aggregates->get();
    }

    /**
     * @return iterable
     */
    protected function getVotesDown(): iterable
    {
        $aggregates = new Aggregates\DownVotes;
        $aggregates->setLimit(10000);
        $aggregates->setType($this->type);
        $aggregates->setSubtype($this->subtype);
        $aggregates->setFrom($this->from);
        $aggregates->setTo($this->to);

        return $aggregates->get();
    }
}
