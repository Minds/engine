<?php

namespace Minds\Core\Email\Confirmation;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Interfaces\BasicCacheInterface;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 * Email confirmation token cacher.
 */
class TokenCache implements BasicCacheInterface
{
    // Base of key to be used with sprintf - interpolating user guid.
    const CACHE_KEY_BASE = 'email-confirmation:%s';

    // Storage time in whole seconds - 1 day.
    const CACHE_TIME_SECONDS = 86400;
    
    // User we are storing or getting for.
    private ?User $user = null;

    /**
     * Constructor.
     * @param ?PsrWrapper $cache - PsrWrapper around cache.
     */
    public function __construct(
        private ?PsrWrapper $cache = null
    ) {
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Gets value from cache using instance generated cache key, based on user guid.
     * @return string
     */
    public function get()
    {
        return $this->cache->get(
            $this->getCacheKey()
        );
    }

    /**
     * Sets value in cache using instance generated cache key, based on user guid.
     * @return self
     */
    public function set($value)
    {
        $this->cache->set(
            $this->getCacheKey(),
            $value,
            self::CACHE_TIME_SECONDS
        );
        return $this;
    }

    /**
     * Sets instance member user.
     * @param User $user - user to set.
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Gets instance member user.
     * @return ?User - instance member user.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Gets cache key based on instance member user guid.
     * @return string cache key.
     */
    private function getCacheKey(): string
    {
        if (!$user = $this->getUser()) {
            throw new ServerErrorException('No user set for email confirmation token cache');
        }
        return sprintf(self::CACHE_KEY_BASE, $user->getGuid());
    }
}
