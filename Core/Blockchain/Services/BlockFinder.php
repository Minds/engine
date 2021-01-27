<?php
/**
 * A simple service to find a block from a timestamp
 */
namespace Minds\Core\Blockchain\Services;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;

class BlockFinder
{
    /** @var string */
    const CACHE_KEY_PREFIX = 'service:blockfinder';

    /** @var Etherscan */
    protected $etherscan;

    /** @var PsrWrapper */
    protected $cache;

    /**
     * @param Etherscan $etherscan
     * @param PsrWrapper $cache
     */
    public function __construct(Etherscan $etherscan = null, $cache = null)
    {
        $this->etherscan = $etherscan ?? new Etherscan();
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Returns the closest block number to the provided timestamp
     * @param int $unixTimestamp
     * @return int
     */
    public function getBlockByTimestamp(int $unixTimestamp): int
    {
        $cacheKey = static::CACHE_KEY_PREFIX . ':' . $unixTimestamp;
        if ($blockNumber = $this->cache->get($cacheKey)) {
            return (int) $blockNumber;
        }

        $blockNumber = $this->etherscan->getBlockNumberByTimestamp($unixTimestamp);
        $this->cache->set($cacheKey, $blockNumber);

        return (int) $blockNumber;
    }
}
