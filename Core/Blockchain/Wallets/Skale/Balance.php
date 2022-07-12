<?php
namespace Minds\Core\Blockchain\Wallets\Skale;

use Minds\Core\Blockchain\Skale\Token;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;

/**
 * Balance functions for MINDS token on SKALE network.
 * Utilizes cache to avoid repeat requests through SKALE service.
 */
class Balance
{
    /** @var int - ttl of cache entries for balance */
    private int $cacheTtlSeconds = 60;

    /**
     * Constructor.
     * @param Token|null $token - Minds token on SKALE network.
     * @param PsrWrapper|null $cache - cache for storing balance.
     */
    public function __construct(
        private ?Token $token = null,
        private ?PsrWrapper $cache = null,
        private ?Config $config = null
    ) {
        $this->token ??= Di::_()->get('Blockchain\Skale\Token');
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
        $this->config ??= Di::_()->get('Config');

        if ($cacheTtlSeconds = $this->config->get('blockchain')['skale']['balance_cache_ttl_seconds'] ?? false) {
            $this->cacheTtlSeconds = $cacheTtlSeconds;
        }
    }

    /**
     * Return token balance in wei for a given address.
     * @param string $address - address to give token balance in wei for.
     * @param bool $useCache - whether cache should be used.
     * @return string|null - token balance in wei, or null.
     */
    public function getTokenBalance(string $address, bool $useCache = true): ?string
    {
        $cacheKey = "skale:minds:balance:{$address}";

        if ($useCache) {
            $balance = $this->cache->get($cacheKey);

            if ($balance) {
                return unserialize($balance);
            }
        }

        $balance = $this->token->balanceOf($address);

        if ($balance === null) {
            return null;
        }
        
        if ($useCache) {
            $this->cache->set($cacheKey, serialize($balance), $this->cacheTtlSeconds);
        }

        return $balance;
    }

    /**
     * Return sFuel (equivalent of Ether on SKALE network) balance in wei
     * for a given address.
     * @param string $address - address to give sFuel balance in wei for.
     * @param bool $useCache - whether cache should be used.
     * @return string|null - sFuel balance in wei, or null.
     */
    public function getSFuelBalance(string $address, bool $useCache = true): ?string
    {
        $cacheKey = "skale:sfuel:balance:{$address}";

        if ($useCache) {
            $balance = $this->cache->get($cacheKey);

            if ($balance) {
                return unserialize($balance);
            }
        }

        $balance = $this->token->sFuelBalanceOf($address);

        if ($balance === null) {
            return null;
        }
        
        if ($useCache) {
            $this->cache->set($cacheKey, serialize($balance), $this->cacheTtlSeconds);
        }

        return $balance;
    }
}
