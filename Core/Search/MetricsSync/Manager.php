<?php
/**
 * MetricsSync Manager
 * @author edgebal / mark
 */

namespace Minds\Core\Search\MetricsSync;

use Exception;
use Minds\Core\Log\Logger;
use Minds\Core\Di\Di;

class Manager
{
    /** @var MetricResolverInterface[] */
    const DEFAULT_RESOLVERS = [
        Resolvers\VotesUpMetricResolver::class,
        Resolvers\VotesDownMetricResolver::class,
        Resolvers\CommentsCountMetricResolver::class,
    ];

    /** @var string */
    protected $type;

    /** @var string */
    protected $subtype = '';

    /** @var int */
    protected $from;

    /** @var int */
    protected $to;

    /** @var string */
    protected $metric;

    /** @var Repository */
    protected $repository;

    /** @var Logger */
    protected $logger;

    /**
     * Sync constructor.
     * @param Repository $repository
     */
    public function __construct(
        $repository = null
    ) {
        $this->repository = $repository ?? new Repository();
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    /**
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $subtype
     * @return self
     */
    public function setSubtype(string $subtype): self
    {
        $this->subtype = $subtype;
        return $this;
    }

    /**
     * @param int $from
     * @return self
     */
    public function setFrom(int $from): self
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @param int $to
     * @return self
     */
    public function setTo(int $to): self
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @param string $metric
     * @return self
     */
    public function setMetric(string $metric): self
    {
        $this->metric = $metric;
        return $this;
    }

    /**
     * @param MetricResolverInterface[] $resolvers
     * @throws Exception
     */
    public function run(array $resolvers = []): void
    {
        // If no resolvers set, then use our default
        if (empty($resolvers)) {
            $resolvers = array_map(function ($class) {
                return $class;
            }, self::DEFAULT_RESOLVERS);
        }

        // Hydrate resolvers if we need to
        $resolvers = array_map(function ($class) {
            return is_object($class) ? $class : new $class();
        }, $resolvers);

        // Sync

        $i = 0;
        foreach ($resolvers as $resolver) {
            $resolver
                ->setType($this->type)
                ->setSubtype($this->subtype)
                ->setFrom($this->from)
                ->setTo($this->to);

            foreach ($resolver->get() as $metric) {
                try {
                    $this->repository->add($metric);
                } catch (Exception $e) {
                    $this->logger->error((string)$e);
                }
                $this->logger->info(sprintf("\n#%s: %s -> %s = %s", ++$i, $metric->getGuid(), $metric->getMetric(), $metric->getCount()));
            }
        }

        // Clear any pending bulk inserts
        $this->repository->bulk();
    }
}
