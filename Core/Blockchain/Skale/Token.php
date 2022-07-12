<?php

namespace Minds\Core\Blockchain\Skale;

use Exception;
use Minds\Core\Blockchain\Services\Skale as SkaleClient;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
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
        private ?Config $config = null,
        private ?Logger $logger = null
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->client ??= Di::_()->get('Blockchain\Services\Skale');
        $this->logger ??= Di::_()->get('Logger');

        $skaleConfig = $this->config->get('blockchain')['skale'] ?? false;

        if (!$skaleConfig || !isset($skaleConfig['minds_token_address'])) {
            throw new Exception('SKALE MINDS token address not configured');
        }

        $this->tokenAddress = $skaleConfig['minds_token_address'];
    }

    /**
     * Gets an account's balance of token in wei.
     * @param string $account - address of the account to check.
     * @param int|null $blockNumber - blocknumber to get balance for - if null will get latest block.
     * @return string - balance as string, in wei.
     */
    public function balanceOf(string $account, ?int $blockNumber = null): ?string
    {
        try {
            $result = $this->client->call($this->tokenAddress, 'balanceOf(address)', [$account], $blockNumber);
            return (string) BigNumber::fromHex($result);
        } catch (\Exception $e) {
            $this->logger->error($e);
            return "0";
        }
    }

    /**
     * Gets an account's sFuel balance (network equivalent of Ether) in wei.
     * @param $account - address of the account to check.
     * @return string - balance as string, in wei.
     */
    public function sFuelBalanceOf(string $account): string
    {
        try {
            $result = $this->client->request('eth_getBalance', [$account, "latest"]);
            return (string) BigNumber::fromHex($result);
        } catch (\Exception $e) {
            $this->logger->error($e);
            return "0";
        }
    }
}
