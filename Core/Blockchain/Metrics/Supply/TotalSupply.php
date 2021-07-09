<?php
namespace Minds\Core\Blockchain\Metrics\Supply;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Metrics;

class TotalSupply extends Metrics\AbstractBlockchainMetric implements Metrics\BlockchainMetricInterface
{
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
        // Find out best guess blockNumber
        $blockNumber = $this->blockFinder->getBlockByTimestamp($this->to);
        return  BigDecimal::of($this->token->totalSupply($blockNumber))->toScale($this->token->getDecimals());
    }
}
