<?php
namespace Minds\Core\Boost\LiquiditySpot;

use Brick\Math\BigDecimal;
use Minds\Core\Di\Di;
use Minds\Core\Counters;
use Minds\Core\Data\Redis;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Blockchain\LiquidityPositions;
use Minds\Core\Blockchain\LiquidityPositions\LiquidityPositionSummary;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;

/**
 * How this works:
 * 1) All these boosts are fetched from redis
 * 2) A backend process pushes the 'boost' entity to redis
 */
class Manager
{
    /** @var LiquidityPositions\Manager */
    protected $liquidityPositionsManager;

    /** @var Counters */
    protected $counters;

    /** @var Redis\Client */
    protected $redis;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Logger */
    protected $logger;

    /** @var Delegates\AnalyticsDelegate */
    protected $analyticsDelegate;

    /** @var string */
    const DB_KEY_SPOT = 'boost:liquidity-spot';

    public function __construct(
        LiquidityPositions\Manager $liquidityPositionsManager = null,
        Counters $counters = null,
        Redis\Client $redis = null,
        EntitiesBuilder $entitiesBuilder = null,
        Logger $logger = null,
        Delegates\AnalyticsDelegate $analyticsDelegate = null
    ) {
        $this->liquidityPositionsManager = $liquidityPositionsManager ?? Di::_()->get('Blockchain\LiquidityPositions\Manager');
        $this->counters = $counters ?? new Counters();
        $this->redis = $redis ?? Di::_()->get('Redis');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->analyticsDelegate = $analyticsDelegate ?? new Delegates\AnalyticsDelegate();
    }

    /**
     * Returns a boost if we have one
     */
    public function get(): ?Boost
    {
        $boost = $this->redis->get(static::DB_KEY_SPOT);

        if (!$boost) {
            return null;
        }

        // De-serialize
        $boost = unserialize($boost);

        // Build the entity
        $entityGuid = $boost->getEntityGuid();
        $entity = $this->entitiesBuilder->single($entityGuid);

        if (!$entity) {
            return null;
        }

        $boost->setEntity($entity);

        // Increment the counters

        $this->counters->increment(0, $this->getMetricsKey());
        $this->counters->increment($boost->getEntityGuid(), $this->getMetricsKey());

        // Call the delegates

        $this->analyticsDelegate->onGet($boost);

        return $boost;
    }

    /**
     * Intended to be run by a background cli job frequently
     * @return void
     */
    public function sync(): void
    {
        /** @var LiquidityPositionSummary[] */
        $liquidityPositions  = array_filter($this->liquidityPositionsManager->getAllProvidersSummaries(), function ($liquidityPosition) {
            return !$liquidityPosition->isLiquiditySpotOptOut();
        });

        // What share of liquidityProviders have unique address
        $liquidityPositionsCumulativePct = BigDecimal::sum(...array_map(function ($liquidityPosition) {
            return $liquidityPosition->getShareOfLiquidity()
                ->getUsd();
        }, $liquidityPositions))->toFloat() / 1;

        // How many views have we delivered today
        $totalViews = $this->counters->get(0, $this->getMetricsKey(), false) ?: 1;

        // Find nearest eligible user

        foreach ($liquidityPositions as $liquidityPosition) {
            // What is our share?
            $share = $liquidityPosition
                        ->getShareOfLiquidity()
                        ->getUsd()
                        ->toFloat() / $liquidityPositionsCumulativePct;

            // How many views have we had today
            $views = $this->counters->get($liquidityPosition->getUserGuid(), $this->getMetricsKey(), false);

            // What percentage is our views out of total delivered today?
            $viewsPct = $views / $totalViews;

            // Is the percentage higher than our liquidity position?
            if ($viewsPct > $share) {
                $this->logger->info("{$liquidityPosition->getUserGuid()}  $viewsPct/$share ($views/$totalViews), higher than available share");
                continue;
            }

            $boost = new Boost();
            $boost->setEntityGuid($liquidityPosition->getUserGuid());

            // Push to redis

            $this->redis->set(static::DB_KEY_SPOT, serialize($boost));

            $this->logger->info("{$liquidityPosition->getUserGuid()}  $viewsPct/$share ($views/$totalViews), pushing to spot");

            return;
        }
    }

    /**
     * Returns the metrics key we use to count the total and boost counts
     * @return string
     */
    private function getMetricsKey(): string
    {
        $dayTs = strtotime('midnight');
        return static::DB_KEY_SPOT . ":$dayTs";
    }
}
