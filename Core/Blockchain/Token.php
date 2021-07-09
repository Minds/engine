<?php

/**
 * Token Manager
 *
 * @author emi
 */

namespace Minds\Core\Blockchain;

use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;

class Token
{
    /** @var Manager */
    protected $manager;

    /** @var Services\Ethereum */
    protected $client;

    /** @var string */
    protected $tokenAddress;

    /** @var int */
    protected $tokenDecimals;

    /**
     * Token constructor.
     * @param null $config
     * @throws \Exception
     */
    public function __construct($manager = null, $client = null)
    {
        $this->manager = $manager ?: Di::_()->get('Blockchain\Manager');
        $this->client = $client ?: Di::_()->get('Blockchain\Services\Ethereum');

        if (!$contract = $this->manager->getContract('token')) {
            throw new \Exception('No token set');
        }

        $this->tokenAddress = $contract->getAddress();
        $this->tokenDecimals = $contract->getExtra()['decimals'] ?: 18;
    }

    /**
     * Gets an account's balance of token
     * @param string $account
     * @param int $blockNumber
     * @return string
     * @throws \Exception
     */
    public function balanceOf(string $account, int $blockNumber = null)
    {
        try {
            $result = $this->client->call($this->tokenAddress, 'balanceOf(address)', [$account], $blockNumber);

            return (string) BigNumber::fromHex($result);
        } catch (\Exception $e) {
            return "0";
        }
    }

    /**
     * Gets an account's Ether balance
     * @param $account - address
     * @return string - balance cast as string.
     */
    public function etherBalanceOf(string $account): string
    {
        try {
            $result = $this->client->request('eth_getBalance', [$account, "latest"]);
            return (string) BigNumber::fromHex($result);
        } catch (\Exception $e) {
            return "0";
        }
    }

    /**
     * Gets the total supply of token
     * @param int $blockNumber
     * @return double
     */
    public function totalSupply(int $blockNumber = null)
    {
        $result = $this->client->call($this->tokenAddress, 'totalSupply()', [], $blockNumber);

        return $this->fromTokenUnit(BigNumber::fromHex($result));
    }

    /**
     * @param $amount
     * @return string
     * @throws \Exception
     */
    public function toTokenUnit($amount)
    {
        return (string) BigNumber::toPlain($amount, $this->tokenDecimals);
    }

    /**
     * @param $amount
     * @return float
     * @throws \Exception
     */
    public function fromTokenUnit($amount)
    {
        return (string) BigNumber::fromPlain($amount, $this->tokenDecimals);
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return $this->tokenDecimals;
    }
}
