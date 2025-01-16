<?php

/**
 * Token Manager
 *
 * @author emi
 */

namespace Minds\Core\Blockchain;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;

class Token
{
    /** @var int */
    protected $tokenDecimals = 18;

    /**
     * Token constructor.
     * @param null $config
     * @throws \Exception
     */
    public function __construct(
        protected ?Manager $manager = null,
        protected ?Services\Ethereum $client = null,
        protected ?Config $config = null,
    ) {
        $this->manager = $manager ?: Di::_()->get('Blockchain\Manager');
        $this->client = $client ?: Di::_()->get('Blockchain\Services\Ethereum');
        $this->config ??= Di::_()->get(Config::class);

        if (!$this->manager->getContract('token')) {
            throw new \Exception('No token set');
        }
    }

    /**
     * Gets an account's balance of token
     * @param string $account
     * @param int $blockNumber
     * @return string
     * @throws \Exception
     */
    public function balanceOf(string $account, int $blockNumber = null, int $chainId = Util::BASE_CHAIN_ID)
    {
        try {
            $result = $this->client->call($this->getTokenAddress($chainId), 'balanceOf(address)', [$account], $blockNumber, $chainId);

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
    public function etherBalanceOf(string $account, int $chainId = Util::BASE_CHAIN_ID): string
    {
        try {
            $result = $this->client->request('eth_getBalance', [$account, "latest"], $chainId);
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
    public function totalSupply(int $blockNumber = null, int $chainId = Util::BASE_CHAIN_ID)
    {
        $result = $this->client->call($this->getTokenAddress($chainId), 'totalSupply()', [], $blockNumber, $chainId);

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

    private function getTokenAddress(int $chainId = Util::BASE_CHAIN_ID): string
    {
        return $this->config?->get('blockchain')['token_addresses'][$chainId];
    }
}
