<?php

/**
 * SKALE RPC Manager
 *
 * @author Ben
 */

namespace Minds\Core\Blockchain\Services;

use kornrunner\Keccak;
use Minds\Core\Blockchain\Config;
use Minds\Core\Blockchain\GasPrice;
use Minds\Core\Di\Di;
use Minds\Core\Http\Curl\JsonRpc;
use MW3;

class Skale
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

    /** @var GasPrice */
    protected $gasPrice;

    /**
     * Ethereum constructor.
     * @param null|mixed $config
     * @param null|mixed $jsonRpc
     * @throws \Exception
     */
    public function __construct($config = null, $jsonRpc = null, $sign = null, $sha3 = null, $gasPrice = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
        $this->jsonRpc = $jsonRpc ?: Di::_()->get('Http\JsonRpc');

        $this->sign = $sign ?: new MW3\Sign;
        $this->sha3 = $sha3 ?: new MW3\Sha3;
        $this->gasPrice = $gasPrice ?: Di::_()->get('Blockchain\GasPrice');
    }

    /**
     * Sends a request to the best Ethereum RPC endpoint
     * @param $method
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function request($method, array $params = [])
    {
        $response = $this->jsonRpc->post($this->getBestEndpoint(), [
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
    public function call($contract, $contractMethodDeclaration, array $params, int $blockNumber = null)
    {
        return $this->request('eth_call', [[
            'to' => $contract,
            'data' =>  $this->encodeContractMethod($contractMethodDeclaration, $params)
        ],  $blockNumber ? '0x' . dechex($blockNumber) : 'latest' ]);
    }

    /**
     * Returns the next available RPC endpoint
     * @return string
     * @throws \Exception
     */
    protected function getBestEndpoint()
    {
        $config = $this->config->get('skale');

        if (!$config['rpc_url']) {
            throw new \Exception('No RPC endpoints available');
        }
        return $config['rpc_url'];
    }
}
