<?php
namespace Minds\Core\Blockchain\Metrics;

use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;

class Manager
{
    /** @var string[] */
    const METRICS = [
        Supply\CirculatingSupply::class,
        Supply\MarketCap::class,
        Supply\TotalSupply::class,
        Supply\TokenHolders::class,
        Supply\TokensReclaimed::class,
        Supply\TokensRewarded::class,
        Transactions\TransactionsCount::class,
        Transactions\TransactionsUnique::class,
        Transactions\TransactionsVolume::class,
        Rewards\EngagementScore::class,
        Rewards\HoldingScore::class,
        Rewards\LiquidityScore::class,
        Liquidity\LiquidityTradedVolume::class,
        Liquidity\LiquidityFees::class,
        Liquidity\LiquidityTotal::class,
    ];

    /** @var int */
    protected $startTs;

    /** @var int */
    protected $endTs;

    /** @var Repository */
    protected $repository;

    /** @var Logger */
    protected $logger;

    public function __construct(Repository $repository = null, Logger $logger = null)
    {
        $this->repository = $repository ?? new Repository();
        $this->logger = $logger ?? Di::_()->get('Logger');

        $this->endTs = time();
    }

    /**
     * Sets the time boundaries for the metrics
     * @param int $startTs
     * @param int $fromTs
     * @return self
     */
    public function setTimeBoundary(int $startTs, int $endTs): self
    {
        $manager = clone $this;
        $manager->startTs = $startTs;
        $manager->endTs = $endTs;
        return $manager;
    }

    /**
     * Returns hydrated metrics per list above
     * @return AbstractBlockchainMetric[]
     */
    public function getAll(): array
    {
        $hydratedMetrics = [];
        foreach (static::METRICS as $metricId) {
            $metric = $this->getMetric($metricId, $this->endTs);

            // Fetch the comparatrive
            $comparative = $this->getMetric($metricId, $this->endTs - $metric->getComparativeOffset());

            $metric->setComparative($comparative);

            $hydratedMetrics[$this->getClassName($metric)] = $metric;
        }

        return $hydratedMetrics;
    }

    /**
     * Returns a metric from the id
     * @param string $id
     * @return AbstractBlockchainMetric
     */
    public function getMetric(string $id, int $timestamp): AbstractBlockchainMetric
    {
        $metric = new $id();
        $metric->setTo($timestamp);

        $opts = new MetricsQueryOpts();
        $opts->setMetricId($metric->getId())
            ->setTimestamp($metric->getTimestamp());

        $metrics = $this->repository->getList($opts);

        if ($metrics && $metrics[0]) {
            $metric = $metrics[0];
        }

        return $metric;
    }

    /**
     * Syncs the metrics to the database for improved performance
     * @return void
     */
    public function sync(): void
    {
        foreach (static::METRICS as $metric) {
            $metric = new $metric();
            $metric->setFrom($this->startTs)
                ->setTo($this->endTs);

            $metric->setOnchain($metric->fetchOnchain());
            $metric->setOffchain($metric->fetchOffchain());

            $this->logger->info("{$metric->getId()} onchain: {$metric->getOnchain()} offchain: {$metric->getOffchain()}");

            $this->repository->add($metric);
        }
    }

    /**
     * @param AbstractBlockchainMetric $metric
     * @return string
     */
    protected function getClassName(AbstractBlockchainMetric $metric): string
    {
        $className = get_class($metric);
        return str_replace('Minds\\Core\\Blockchain\\Metrics\\', '', $className);
    }
}
