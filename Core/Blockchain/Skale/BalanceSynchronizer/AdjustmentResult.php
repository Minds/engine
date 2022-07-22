<?php

namespace Minds\Core\Blockchain\Skale\BalanceSynchronizer;

use Minds\Traits\MagicAttributes;

/**
 * Object holding the result of an adjustment.
 */
class AdjustmentResult
{
    use MagicAttributes;

    /** @var string - the transaction hash of the adjustment */
    private string $txHash;

    /** @var string - the difference corrected in wei */
    private string $differenceWei;

    /** @var string - username of the user the adjustment was made for */
    private string $username;

    /**
     * Overrides toString function of class, so it can be output in logs as a readable string.
     * @return string - readable string containing information.
     */
    public function __toString()
    {
        return "User: $this->username, SKALE balance offset: $this->differenceWei wei, Correction TX: $this->txHash";
    }
}
