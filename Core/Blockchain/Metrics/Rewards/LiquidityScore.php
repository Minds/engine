<?php
namespace Minds\Core\Blockchain\Metrics\Rewards;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Metrics;
use Minds\Core\Rewards\Manager;

class LiquidityScore extends AbstractBlockchainRewardMetric implements Metrics\BlockchainMetricInterface
{
    /** @var string */
    protected $format = 'points';

    /**
     * @return BigDecimal
     */
    public function fetchOffchain(): BigDecimal
    {
        return $this->getScore(Manager::REWARD_TYPE_LIQUIDITY);
    }

    /**
     * @return BigDecimal
     */
    public function fetchOnchain(): BigDecimal
    {
        return BigDecimal::of(0);
    }
}
