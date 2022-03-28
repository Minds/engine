<?php
namespace Minds\Core\Blockchain\TokenPrices;

use Minds\Core\Blockchain\Uniswap;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

use Minds\Core\Data\cache\PsrWrapper;

class Manager
{
    public function __construct(
        protected ?Uniswap\Client $uniswapClient = null,
        protected ?Config $config = null,
        protected ?PsrWrapper $cache = null
    ) {
        $this->uniswapClient = $uniswapClient ?: Di::_()->get('Blockchain\Uniswap\Client');
        $this->config = $config ?: Di::_()->get('Config');
        $this->cache = $cache ?: Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Returns an array of prices
     * @return array
     */
    public function getPrices(): array
    {
        $tokenAddress = $this->config->get('blockchain')['token_address'];

        $cacheKey = "blockchain::token-balance::$tokenAddress";

        if ($cached = $this->cache->get($cacheKey)) {
            $prices = unserialize($cached);
        } else {
            $prices = $this->uniswapClient->getTokenUsdPrices($tokenAddress);
            $this->cache->set($cacheKey, serialize($prices), 300); // 5 mins
        }

        return [
            'eth' => $prices['eth'],
            'minds' => $prices['token'],
        ];
    }
}
