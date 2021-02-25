<?php
namespace Minds\Core\Blockchain\Metrics\Liquidity;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Metrics;
use Minds\Core\Blockchain\TokenPrices;
use Minds\Core\Blockchain\LiquidityPositions;

class LiquidityFees extends Metrics\AbstractBlockchainMetric implements Metrics\BlockchainMetricInterface
{
    /** @var LiquidityTradedVolume */
    protected $liquidityTradedVolume;

    /** @var TokenPrices\Manager */
    protected $tokenPricesManager;

    /** @var string */
    protected $format = 'usd';

    public function __construct($liquidityTradedVolume = null, ...$injectables)
    {
        parent::__construct(...$injectables);
        $this->liquidityTradedVolume = $liquidityTradedVolume ?? new LiquidityTradedVolume();
        $this->tokenPricesManager = $tokenPricesManager ?? new TokenPrices\Manager();
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
        $tradedVolume = $this->cache->get(LiquidityTradedVolume::ONCHAIN_CACHE_KEY);

        if (!$tradedVolume) {
            return BigDecimal::of(0);
        }

        return BigDecimal::of($tradedVolume)->multipliedBy(0.003);
    }
}
