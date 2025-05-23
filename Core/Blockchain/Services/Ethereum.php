<?php

/**
 * Ethereum RPC Manager
 *
 * @author Emi, Mark
 */

namespace Minds\Core\Blockchain\Services;

use kornrunner\Keccak;
use Minds\Core\Blockchain\Config;
use Minds\Core\Blockchain\GasPrice;
use Minds\Core\Blockchain\Util;
use Minds\Core\Di\Di;
use Minds\Core\Http\Curl\JsonRpc;
use Minds\Core\Util\BigNumber;
use MW3;

class Ethereum
{
    /** @var Config */
    protected $config;

    /** @var JsonRpc\Client $jsonRpc */
    protected $jsonRpc;

    /** @var string[] $endpoints */
    protected $endpoints;

    /** @var MW3\Sign $sign */
    protected $sign;

    /** @var MW3\Sha3 $sha3 */
    protected $sha3;

    /** @var array $nonces */
    private $nonces = [];

    /**
     * Ethereum constructor.
     * @param null|mixed $config
     * @param null|mixed $jsonRpc
     * @throws \Exception
     */
    public function __construct($config = null, $jsonRpc = null, $sign = null, $sha3 = null)
    {
        $this->config = $config ?: new Config();
        $this->jsonRpc = $jsonRpc ?: Di::_()->get('Http\JsonRpc');

        $this->sign = $sign ?: new MW3\Sign;
        $this->sha3 = $sha3 ?: new MW3\Sha3;
    }

    /**
     * Sets the config key to be used
     * @param $configKey
     * @return $this
     */
    public function useConfig($configKey)
    {
        $this->config->setKey($configKey);
        return $this;
    }

    /**
     * Sends a request to the best Ethereum RPC endpoint
     * @param $method
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function request($method, array $params = [], int $chainId = Util::BASE_CHAIN_ID)
    {
        $response = $this->jsonRpc->post($this->getRpcEndpoint($chainId), [
            'method' => $method,
            'params' => $params
        ]);

        if (!$response) {
            throw new \Exception('Server did not respond');
        }

        if (isset($response['error'])) {
            throw new \Exception("[Ethereum] {$response['error']['code']}: {$response['error']['message']}");
        }

        return $response['result'];
    }

    /**
     * Returns the Ethereum's non-standard SHA3 hash for the given string
     * @param string $string
     * @return string
     */
    public function sha3($string)
    {
        return Keccak::hash($string, 256);
    }

    /**
     * Encodes a contract call, suitable for eth_call and eth_sendRawTransaction
     * @param string $contractMethodDeclaration
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function encodeContractMethod($contractMethodDeclaration, array $params)
    {
        // Method Signature: first 4 bytes (8 hex digits)
        $contractMethodSignature = substr($this->sha3($contractMethodDeclaration), 0, 8);

        $contractMethodParameters = '';

        foreach ($params as $param) {
            if (strpos($param, '0x') !== 0) {
                // TODO: Implement parameter types, etc
                throw new \Exception('Ethereum::call only supports raw hex parameters');
            }

            $hex = substr($param, 2);
            $contractMethodParameters .= str_pad($hex, 64, '0', STR_PAD_LEFT);
        }

        return '0x' . $contractMethodSignature . $contractMethodParameters;
    }

    /**
     * Runs a raw method unsigned call in a contract
     * @param string $contract
     * @param string $contractMethodDeclaration
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function call($contract, $contractMethodDeclaration, array $params, int $blockNumber = null, int $chainId = Util::BASE_CHAIN_ID)
    {
        return $this->request('eth_call', [[
            'to' => $contract,
            'data' =>  $this->encodeContractMethod($contractMethodDeclaration, $params)
        ],  $blockNumber ? '0x' . dechex($blockNumber) : 'latest' ], $chainId);
    }

    /**
     * Signs a transaction
     * @param string $privateKey
     * @param array $transaction
     * @return string
     * @throws \Exception
     */
    public function sign($privateKey, array $transaction)
    {
        $tx = json_encode($transaction);

        return $this->sign->setPrivateKey($privateKey)
            ->setTx($tx)
            ->sign();
    }

    /**
     * @return string
     */
    public function recoverAddress($message, $signature): string
    {
        return $this->sign->recoverAddress($message, $signature);
    }

    /**
     * @param string $message
     * @param string $signature
     * @return string
     */
    public function verifyMessage(string $message, string $signature): string
    {
        return $this->sign->verifyMessage($message, $signature);
    }

    /**
     * Sends a raw transaction
     * @param string $privateKey
     * @param array $transaction
     * @return mixed
     * @throws \Exception
     */
    public function sendRawTransaction($privateKey, array $transaction, int $chainId = Util::BASE_CHAIN_ID)
    {
        $transaction['chainId'] = $chainId;

        if (!isset($transaction['from']) || !isset($transaction['gasLimit'])) {
            throw new \Exception('Transaction must have `from` and `gasLimit`');
        }

        if (!isset($transaction['gasPrice'])) {
            $transaction['gasPrice'] = $this->getLatestGasPrice($chainId);
        }

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


        return $this->request('eth_sendRawTransaction', [ $signedTx ], $chainId);
    }

    /**
     * Query RPC endpoint for current gas price.
     * @throws \Exception - on error.
     * @return string|null current gas price.
     */
    public function getCurrentGasPrice(): ?string
    {
        return $this->request('eth_gasPrice', []);
    }

    /**
     * Returns the latest gas price
     */
    private function getLatestGasPrice(int $chainId): string
    {
        $config = $this->config->get();
        $defaultGasPrice = $config['server_gas_price'] ?: 1;

        $response = $this->request('eth_gasPrice', [], $chainId);

        if (!$response) {
            error_log('Core\Blockchain\GasPrice: Invalid eth_gasPrice response');
            return BigNumber::_($defaultGasPrice * 1000000000)->toHex(true);
        }

        return $response;
    }

    /**
     * Returns the relevant rpc endpoint
     * @return string
     * @throws \Exception
     */
    protected function getRpcEndpoint(int $chainId)
    {
        $config = $this->config->get();

        if (!$config['rpc_endpoints']) {
            throw new \Exception('No RPC endpoints available');
        }

        return $config['rpc_endpoints'][$chainId];
    }
}
