<?php

namespace Minds\Core\Blockchain\EthereumGasPrice;

use Minds\Core\Util\BigNumber;
use Minds\Traits\MagicAttributes;

/**
 * Gas price estimate class for 1559 compatible transactions.
 */
class GasPriceEstimate
{
    use MagicAttributes;

    /** @var BigNumber - number of the block data that estimates have been derived from. */
    protected $blockNum;

    /**
     * @var BigNumber - base fee per gas, should be pulled from the current pending block.
     * This variable is set by the network, and can increment and decrement by 12.5% every block.
     */
    protected $baseFeePerGas;

    /** @var BigNumber - maximum priority fee to be paid to miners. */
    protected $maxPriorityFeePerGas;

    /**
     * maxFeePerGas must at minimum, be (baseFeePerGas + maxPriorityFeePerGas).
     * In this calculation we do (baseFeePerGas * 2) + maxPriorityFeePerGas to give a buffer incase
     * the block we have derived our estimates from is mined whilst we are processing,
     * and the baseFee shifts as a result.
     *
     * This is setting the MAXIMUM fee per gas, the baseFee is set by the network, thus any
     * unused gas once the priority fee and base fee have been removed should be returned.
     */
    public function getMaxFeePerGas(): BigNumber
    {
        return $this->baseFeePerGas->mul(2)->add($this->maxPriorityFeePerGas);
    }
}
