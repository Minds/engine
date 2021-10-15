<?php
namespace Minds\Core\Blockchain\EthereumGasPrice;

use Minds\Core\Blockchain\Services\Ethereum;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;

/**
 * Gas fee estimat manager, for type 2 (post EIP-1559) transactions.
 */
class Manager
{
    /** @var Ethereum - Ethereum service used to dispatch RPC calls. */
    private $eth;

    public function __construct($eth = null)
    {
        $this->eth = $eth ?: Di::_()->get('Blockchain\Services\Ethereum');
    }
   
    /**
     * Estimate and populate a GasPriceEstimate object with the results.
     * @return GasPriceEstimate - estimate of gas price to be mined in current pending block.
     */
    public function estimate(): GasPriceEstimate
    {
        $maxPriorityFeePerGas = BigNumber::fromHex($this->getMaxPriorityFeePerGas());
        $pendingBlock = $this->getCurrentPendingBlock();
        $baseFeePerGas =  BigNumber::fromHex($pendingBlock['baseFeePerGas'][0]);
        $blockNum =  BigNumber::fromHex($pendingBlock['oldestBlock']);

        return (new GasPriceEstimate())
            ->setBlockNum($blockNum)
            ->setBaseFeePerGas($baseFeePerGas)
            ->setMaxPriorityFeePerGas($maxPriorityFeePerGas);
    }

    /**
     * RPC call to eth_maxPriorityFeePerGas that suggests the maximum priority fee to be paid to miners.
     * @return mixed
     */
    private function getMaxPriorityFeePerGas(): mixed
    {
        return $this->eth->request('eth_maxPriorityFeePerGas');
    }

    /**
     * RPC call to get the currently pending block
     * @return mixed
     */
    private function getCurrentPendingBlock(): mixed
    {
        return $this->eth->request('eth_feeHistory', [1, "pending", []]);
    }
}
