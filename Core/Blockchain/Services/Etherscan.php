<?php

/**
 * Etherscan Service
 *
 * @author Nico, Martin
 */

namespace Minds\Core\Blockchain\Services;

use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Http\Curl\Json\Client;

class Etherscan
{
    /** @var Client $http */
    protected $http;

    /** @var string $address */
    protected $address;

    /** @var string $apiKey */
    protected $apiKey;

    /** @var string */
    protected $contractAddress;

    /**
     * Etherscan constructor.
     * @param Http\Json $http
     * @param Config $config
     */
    public function __construct($http = null, $config = null)
    {
        $this->http = $http ?: Di::_()->get('Http\Json');
        $config = $config ?: Di::_()->get('Config');
        $blockchainConfig = $config->get('blockchain');
        $this->apiKey = ($blockchainConfig['etherscan'] ?? [])['api_key'] ?? null;
    }

    /**
     * @param string $address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return $this
     */
    public function setContractAddress($address)
    {
        $this->contractAddress = $address;
        return $this;
    }

    /**
     * @return string
     */
    public function getContractAddress()
    {
        return $this->contractAddress;
    }

    /**
     * Method to get balance eth by address.
     * @return float
     * @throws \Exception
     */
    public function getAccountBalance(int $chainId = 1)
    {
        $balance = $this->request("module=account&chainid=$chainId&action=balance&address={$this->address}&tag=latest&apikey={$this->apiKey}");
        return $balance['result'];
    }

    /**
     * Method to get eth total supply by contract.
     * @return float
     * @throws \Exception
     */
    public function getContractTotalSupply(int $chainId = 1)
    {
        $balance = $this->request("module=stats&chainid=$chainId&action=tokenSupply&contractaddress={$this->contractAddress}&apikey={$this->apiKey}");
        return $balance;
    }

    /**
     * Method to get eth total supply.
     * @return float
     * @throws \Exception
     */
    public function getTotalSupply(int $chainId = 1)
    {
        $balance = $this->request("module=stats&chainid=$chainId&action=tokenSupply&tokenname=MINDS&apikey={$this->apiKey}");
        return $balance;
    }

    /**
     * Proxy method to get transaction count, gives 0x1 as result.
     * @return float
     * @throws \Exception
     */
    public function getTransactionsCount(int $chainId = 1)
    {
        $balance = $this->request("module=proxy&chainid=$chainId&action=eth_getTransactionCount&tag=latest&address={$this->address}&apikey={$this->apiKey}");
        return $balance;
    }

    /**
     * Method to make the actual request by a given endpoint.
     * Get transacionts
     *
     * @param integer $from
     * @param integer $to
     * @param integer $page
     * @param integer $count
     * @return array
     */
    public function getTransactions($from, $to, $page=null, $count=100, int $chainId = 1)
    {
        $endpoint = "module=account&chainid=$chainId&action=txlist&address={$this->address}&startblock={$from}&endblock={$to}&sort=desc".($page ? "&page=$page&offset=$count" : '');
        $endpoint .= "&apikey={$this->apiKey}";

        $transactions = $this->request($endpoint);

        return $transactions['result'];
    }

    /**
     * Get internal transacionts
     *
     * @param integer $from
     * @param integer $to
     * @param integer $page
     * @param integer $count
     * @return array
     */
    public function getInternalTransactions($from, $to, $page=null, $count=100, int $chainId = 1)
    {
        $endpoint = "module=account&chainid=$chainId&action=txlistinternal&address={$this->address}&startblock={$from}&endblock={$to}&sort=desc".($page ? "&page=$page&offset=$count" : '');
        $endpoint .= "&apikey={$this->apiKey}";

        $transactions = $this->request($endpoint);

        return $transactions['result'];
    }

    /**
     * Get Token Transfer Events
     *
     * @param integer $from
     * @param integer $to
     * @param integer $page
     * @param integer $count
     * @return array
     */
    public function getTokenTransactions($from, $to, $page=null, $count=100, int $chainId = 1)
    {
        $endpoint = "module=account&chainid=$chainId&action=tokentx&address={$this->address}&startblock={$from}&endblock={$to}&sort=desc".($page ? "&page=$page&offset=$count" : '');
        $endpoint .= "&apikey={$this->apiKey}";

        $transactions = $this->request($endpoint);

        return $transactions['result'];
    }

    /**
     * Get transaction by hash
     *
     * @param string $hash
     * @return array
     */
    public function getTransaction($hash, int $chainId = 1)
    {
        $result = $this->request("module=proxy&chainid=$chainId&action=eth_getTransactionReceipt&txhash={$hash}&apikey={$this->apiKey}");
        return $result['result'];
    }

    /**
     * Return the number of the last block of the blockchain
     *
     * @return integer
     */
    public function getLastBlockNumber(int $chainId = 1)
    {
        $result = $this->request("module=proxy&chainid=$chainId&action=eth_blockNumber&apikey={$this->apiKey}");
        return hexdec($result['result']);
    }

    /**
     * Return the last block of the blockchain
     *
     * @return int
     */
    public function getLastBlock(int $chainId = 1)
    {
        $number = $this->getLastBlockNumber($chainId);
        if ($number) {
            return $this->getBlock($number, $chainId);
        } else {
            return 0;
        }
    }

    /**
     * Get a block by number
     *
     * @param integer $number
     * @return int
     */
    public function getBlock(int $number, int $chainId = 1)
    {
        $result = $this->request("module=block&chainid=$chainId&action=getblockreward&blockno={$number}&apikey={$this->apiKey}");
        return $result['result'];
    }

    /**
     * Return the block number from a unix timestamp
     * @param int $unixTimestamp
     * @return int
     */
    public function getBlockNumberByTimestamp(int $unixTimestamp, int $chainId = 1): int
    {
        $result = $this->request("module=block&chainid=$chainId&action=getblocknobytime&timestamp=$unixTimestamp&closest=before&apikey={$this->apiKey}");
        return (int) $result['result'];
    }

    /**
     * @param string $endpoint
     * @return array
     * @throws \Exception
     */
    protected function request($endpoint)
    {
        $response = $this->http->get("https://api.etherscan.io/v2/api?{$endpoint}");

        if (!is_array($response)) {
            throw new \Exception('Invalid response');
        }

        return $response;
    }
}
