<?php

namespace Minds\Core\Blockchain\Services;

use Minds\Core\Util\BigNumber;
use Minds\Exceptions\ServerErrorException;

/**
 * SKALE service - extends Ethereum service due to the shared functionality
 * however overrides the RPC endpoints to point at SKALE.
 */
class Skale extends Ethereum
{
    /** @var int gas price in wei */
    private int $gasPriceWei = 100000;

    /** @var array $nonces - incrementing value counting transactions used for tx ordering */
    private $nonces = [];
    
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        if ($gasPriceWei = $this->config->get('blockchain')['skale']['gas_price_wei'] ?? false) {
            $this->gasPriceWei = $gasPriceWei;
        }
    }

    /**
     * Sends a raw transaction
     * @param string $privateKey - private key to send with.
     * @param array $transaction - transaction array with data to send.
     * @return ?string - transaction hash
     * @throws \Exception
     */
    public function sendRawTransaction($privateKey, array $transaction): ?string
    {
        if (!isset($transaction['from']) || !isset($transaction['gasLimit'])) {
            throw new \Exception('Transaction must have `from` and `gasLimit`');
        }

        if (!isset($transaction['gasPrice'])) {
            $transaction['gasPrice'] = $this->gasPriceWei;
        }

        // TODO: Improve nonce handling.
        if (!isset($transaction['nonce'])) {
            if (isset($this->nonces[$transaction['from']])) {
                $this->nonces[$transaction['from']] = $transaction['nonce'] = $this->nonces[$transaction['from']];
            } else {
                $nonce = $this->request('eth_getTransactionCount', [ $transaction['from'], 'pending' ]);
                $this->nonces[$transaction['from']] = $transaction['nonce'] = (int) BigNumber::fromHex($nonce)->toString();
            }
            $this->nonces[$transaction['from']]++; //increase future nonces
        }

        $signedTx = $this->sign($privateKey, $transaction);

        if (!$signedTx) {
            throw new \Exception('Error signing transaction');
        }

        return $this->request('eth_sendRawTransaction', [ $signedTx ]);
    }

    /**
     * Returns the next available RPC endpoint.
     * @return string - best endpoint for RPC to SKALE network.
     * @throws ServerErrorException - if no RPC endpoint is available.
     */
    protected function getBestEndpoint(): string
    {
        $config = $this->config->get()['skale'];

        if (!$config['rpc_endpoints'] && !isset($config['rpc_endpoints'][0])) {
            throw new ServerErrorException('No RPC endpoint available');
        }

        return $config['rpc_endpoints'][0];
    }
}
