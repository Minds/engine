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
        $this->tokensReclaimedForBoost->setTo($this->to)->setFrom($this->from);
        return $this->tokensReclaimedForBoost->fetchOffchain()->plus($this->tokensReclaimedForUpgrades->fetchOffchain());
    }

    /**
     * @return BigDecimal
     */
    public function fetchOnchain(): BigDecimal
    {
        $this->tokensReclaimedForBoost->setTo($this->to)->setFrom($this->from);
        return $this->tokensReclaimedForBoost->fetchOnchain()->plus($this->tokensReclaimedForUpgrades->fetchOnchain());
    }
}
