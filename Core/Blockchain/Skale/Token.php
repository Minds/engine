<?php

namespace Minds\Core\Blockchain\Skale;

use Exception;
use Minds\Core\Blockchain\Services\Skale as SkaleClient;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;

/**
 * SKALE MINDS Token - contains functions get information from the token onchain.
 * such as balances.
 */
class Token
{
    /** @var string - address of token on SKALE network */
    protected $tokenAddress;

    /**
     * Constructor.
     * @param SkaleClient|null $client - SKALE client.
     * @param Config|null $config - config.
     * @throws \Exception - throws if misconfigured.
     */
    public function __construct(
        private ?SkaleClient $client = null,
        private ?Config $config = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->client = $client ?: Di::_()->get('Blockchain\Services\Skale');

        $skaleConfig = $this->config->get('blockchain')['skale'] ?? false;

        if (!$skaleConfig || !isset($skaleConfig['mind_token_address'])) {
            throw new Exception('SKALE MINDS token address not configured');
        }

        $this->tokenAddress = $skaleConfig['mind_token_address'];
    }

    /**
     * Gets an account's balance of token in wei.
     * @param string $account - address to check.
     * @param int|null $blockNumber - blocknumber to get balance for - if null will get latest block.
     * @return ?string - balance in wei.
     */
    public function balanceOf(string $account, ?int $blockNumber = null): ?string
    {
        try {
            $result = $this->client->call($this->tokenAddress, 'balanceOf(address)', [$account], $blockNumber);

            return (string) BigNumber::fromHex($result);
        } catch (\Exception $e) {
            return "0";
        }
    }

    // /**
    //  * Gets an account's Ether balance
    //  * @param $account - address
    //  * @return string - balance cast as string.
    //  */
    // public function etherBalanceOf(string $account): string
    // {
    //     try {
    //         $result = $this->client->request('eth_getBalance', [$account, "latest"]);
    //         return (string) BigNumber::fromHex($result);
    //     } catch (\Exception $e) {
    //         return "0";
    //     }
    // }
}
