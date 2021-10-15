<?php


namespace Minds\Core\Blockchain;

use Minds\Core\Di\Di;
use Minds\Core\Http\Curl\Json\Client;
use Minds\Core\Util\BigNumber;

/**
 * @deprecated The eth_gasPrice RPC call can be considered deprecated as of the ETH London fork.
 * It can be used still however will cost more than when using EIP-1559 style transactions.
 * https://blog.infura.io/london-fork/
 */
class GasPrice
{
    /** @var Client */
    private $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: Di::_()->get('Http\Json');
    }

    /**
     * @param int defaultGasPrice
     * @return double
     * @throws \Exception
     */
    public function getLatestGasPrice($defaultGasPrice = 1)
    {
        $response = $this->client->get('https://api.infura.io/v1/jsonrpc/mainnet/eth_gasPrice');

        if (!is_array($response) || !isset($response['result'])) {
            error_log('Core\Blockchain\GasPrice: Invalid Infura response');
            return BigNumber::_($defaultGasPrice * 1000000000)->toHex(true);
        }

        return $response['result'];
    }
}
