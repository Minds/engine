<?php
namespace Minds\Core\Blockchain\Metrics\Supply;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Metrics;
use Minds\Core\Blockchain\TokenPrices;

class MarketCap extends Metrics\AbstractBlockchainMetric implements Metrics\BlockchainMetricInterface
{
    /** @var CirculatingSupply */
    protected $circulatingSupply;

    /** @var TokenPrices\Manager */
    protected $tokenPricesManager;

    /** @var string */
    protected $format = 'usd';

    public function __construct(CirculatingSupply $circulatingSupply = null, $tokenPricesManager = null, ...$injectables)
    {
        parent::__construct(...$injectables);
        $this->circulatingSupply = $circulatingSupply ?? new CirculatingSupply();
        $this->tokenPricesManager = $tokenPricesManager ?? new TokenPrices\Manager();
    }

    /**
     * @return BigDecimal
     */
    public function fetchOffchain(): BigDecimal
    {
        $tokenPrice = $this->tokenPricesManager->getPrices()['minds'];
        return $this->circulatingSupply->getOffchain()->multipliedBy($tokenPrice);
    }

    /**
     * @return BigDecimal
     */
    public function fetchOnchain(): BigDecimal
    {
        $tokenPrice = $this->tokenPricesManager->getPrices()['minds'];
        return $this->circulatingSupply->getOnchain()->multipliedBy($tokenPrice);
    }
}
