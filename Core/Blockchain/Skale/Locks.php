<?php

namespace Minds\Core\Blockchain\Skale;

use Minds\Core\Di\Di;
use Minds\Core\Data\Locks\Redis as RedisLocks;

/**
 * Lock a custodial wallets SKALE balance by user guid.
 */
class Locks {
    /** 
     * @var string - base for key 
     * - note lock class will prepend `lock:`
     */
    const LOCK_KEY_BASE = 'skale:balance:%s';

    /**
     * Constructor.
     * @param RedisLocks|null $locks - store for locks.
     */
    public function __construct(private ?RedisLocks $locks = null)
    {
        $this->locks = $locks ?: Di::_()->get('Database\Locks');
    }

    /**
     * Whether guid has an applied lock.
     * @param string $guid - guid to check lock for.
     * @return boolean - whether there is a lock applied.
     */
    public function isLocked(string $guid): bool {
        return $this->locks->setKey($this->getKey($guid))
            ->isLocked();
    }

    /**
     * Apply a lock
     * @param string $guid - guid to lock balance for.
     * @param integer $ttl - ttl in cache of lock.
     * @throws LockFailedException - if lock is already in place.
     * @return string - result in redis.
     */
    public function lock(string $guid, int $ttl = 120): string
    {
        return $this->locks->setKey($this->getKey($guid))
            ->setTtl($ttl)
            ->lock();
    }

    /**
     * Remove lock / unlock.
     * @param string $guid - guid to remove lock for.
     * @return bool - true if success.
     */
    public function unlock(string $guid): bool {
        return $this->locks->setKey($this->getKey($guid))
            ->unlock();
    }

    /**
     * Gets key for lock in cache by interpolating guid.
     * @param string $guid - guid to interpolate into key name.
     * @return string - interpolated key name.
     */
    private function getKey(string $guid): string
    {
        return sprintf(self::LOCK_KEY_BASE, $guid);
    }
}
