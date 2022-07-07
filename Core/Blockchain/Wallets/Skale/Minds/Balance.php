<?php
namespace Minds\Core\Blockchain\Wallets\Skale\Minds;

use Minds\Core\Blockchain\Skale\Token;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;

/**
 * Balance functions for MINDS token on SKALE network.
 * Utilizes cache to avoid repeat requests through SKALE service.
 */
class Balance
{
    /**
     * Constructor.
     * @param Token|null $token - Minds token on SKALE network.
     * @param PsrWrapper|null $cache - cache for storing balance.
     */
    public function __construct(
        private ?Token $token = null,
        private ?PsrWrapper $cache = null
    ) {
        $this->token ??= Di::_()->get('Blockchain\Skale\Token');
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Return balance in wei for a given address.
     * @param string $address - address to give balance in wei for.
     * @param bool $useCache - whether cache should be used.
     * @return string|null - balance in wei, or null.
     */
    public function get(string $address, bool $useCache = true): ?string
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
            $this->cache->set($cacheKey, serialize($balance), 60);
        }

        return $balance;
    }
}
