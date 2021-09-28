<?php

/**
 * Skale Token Manager
 *
 * @author Ben
 */

namespace Minds\Core\Blockchain;

use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;

class SkaleToken
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
    public function __construct($client = null, $config = null)
    {
        $this->client = $client ?: Di::_()->get('Blockchain\Services\Skale');
        $this->config = $config ?: Di::_()->get('Config');
        $this->tokenAddress = $this->config->get('skale')['erc20_address'];
        $this->tokenDecimals = 18;
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
}
