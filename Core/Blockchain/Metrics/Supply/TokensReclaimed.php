<?php
namespace Minds\Core\Blockchain\Metrics\Supply;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Metrics;
use Minds\Core\Data\ElasticSearch;

class TokensReclaimed extends Metrics\AbstractBlockchainMetric implements Metrics\BlockchainMetricInterface
{
    /** @var TokensReclaimedForBoost */
    protected $tokensReclaimedForBoost;

    /** @var  TokensReclaimedForUpgrades */
    protected $tokensReclaimedForUpgrades;

    public function __construct($tokensReclaimedForBoost = null, $tokensReclaimedForUpgrades = null, ...$injectables)
    {
        parent::__construct(...$injectables);
        $this->tokensReclaimedForBoost = $tokensReclaimedForBoost ?? new TokensReclaimedForBoost();
        $this->tokensReclaimedForUpgrades = $tokensReclaimedForUpgrades ?? new TokensReclaimedForUpgrades();
    }
    /**
     * @return BigDecimal
     */
    public function fetchOffchain(): BigDecimal
    {
        return $this->tokensReclaimedForBoost->getOffchain()->plus($this->tokensReclaimedForUpgrades->getOffchain());
    }

    /**
     * @return BigDecimal
     */
    public function fetchOnchain(): BigDecimal
    {
        return $this->tokensReclaimedForBoost->getOnchain()->plus($this->tokensReclaimedForUpgrades->getOnchain());
    }
}
