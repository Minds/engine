<?php
namespace Minds\Core\Blockchain\Metrics\Liquidity;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Metrics;
use Minds\Core\Blockchain\LiquidityPositions;

class LiquidityTradedVolume extends Metrics\AbstractBlockchainMetric implements Metrics\BlockchainMetricInterface
{
    /** @var string */
    const ONCHAIN_CACHE_KEY = "blockchain:metrics:liquidity-traded-volume";

    /** @var string */
    protected $format = 'usd';

    /** @var LiquidityPositions\Manager */
    protected $liquidityPositionsManager;

    public function __construct($liquidityPositionsManager = null, ...$injectables)
    {
        parent::__construct(...$injectables);
        $this->liquidityPositionsManager = $liquidityPositionsManager ?? new LiquidityPositions\Manager();
    }

    /**
     * @return BigDecimal
     */
    public function fetchOffchain(): BigDecimal
    {
        return BigDecimal::of(0)->toScale($this->token->getDecimals());
    }

    /**
     * @return BigDecimal
     */
    public function fetchOnchain(): BigDecimal
    {
        $todaysPairs = $this->liquidityPositionsManager
            ->setDateTs($this->to - 60)
            ->getPairs();

        $todaysVolume = BigDecimal::sum(...array_map([$this, 'mapPairToVolume'], $todaysPairs));

        $yesterdaysPairs = $this->liquidityPositionsManager
            ->setDateTs(strtotime('24 hours ago', $this->to))
            ->getPairs();

        $yesterdayVolume = BigDecimal::sum(...array_map([$this, 'mapPairToVolume'], $yesterdaysPairs));

        $volumeUsd = $todaysVolume->minus($yesterdayVolume);

        $this->cache->set(static::ONCHAIN_CACHE_KEY, $volumeUsd, 300);

        return $volumeUsd;
    }

    private function mapPairToVolume($pair): BigDecimal
    {
        return $pair->getUntrackedVolumeUSD();
    }
}
