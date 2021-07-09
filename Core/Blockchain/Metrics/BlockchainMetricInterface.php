<?php
namespace Minds\Core\Blockchain\Metrics;

use Brick\Math\BigDecimal;

interface BlockchainMetricInterface
{
    /**
     * Sets the unix timestamp to stop at
     * @param int $to
     * @return self
     */
    public function setTo(int $to): BlockchainMetricInterface;

    /**
     * Sets the unix timestamp to stop at
     * @param int $from
     * @return self
     */
    public function setFrom(int $from): BlockchainMetricInterface;

    /**
     * @return BigDecimal
     */
    public function fetchOffchain(): BigDecimal;

    /**
     * @return BigDecimal
     */
    public function fetchOnchain(): BigDecimal;
}
