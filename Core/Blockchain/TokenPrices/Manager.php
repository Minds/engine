<?php
namespace Minds\Core\Blockchain\TokenPrices;

use Minds\Core\Blockchain\Uniswap;
use Minds\Core\Blockchain\Util;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

use Psr\SimpleCache\CacheInterface;

class Manager
{
    public function __construct(
        protected ?Uniswap\Client $uniswapClient = null,
        protected ?Config $config = null,
        protected ?CacheInterface $cache = null
    ) {
        $this->uniswapClient = $uniswapClient ?: Di::_()->get('Blockchain\Uniswap\Client');
        $this->config = $config ?: Di::_()->get('Config');
        $this->cache = $cache ?: Di::_()->get('Cache\Cassandra');
    }

    /**
     * Returns an array of prices
     * @return array
     */
    public function getPrices(): array
    {
        $tokenAddress = $this->config->get('blockchain')['token_addresses'][Util::BASE_CHAIN_ID];

        $cacheKey = "blockchain::token-balance::$tokenAddress";

        if ($cached = $this->cache->get($cacheKey)) {
            $prices = unserialize($cached);
        } else {
            $prices = $this->uniswapClient->getTokenUsdPrices($tokenAddress);
            $this->cache->set($cacheKey, serialize($prices), 3600); // 1 hour
        }

        return [
            'eth' => $prices['eth'],
            'minds' => $prices['token'],
        ];
    }
}
