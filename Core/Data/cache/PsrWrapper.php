<?php
/**
 * PsrWrapper
 *
 * @author edgebal
 */

namespace Minds\Core\Data\cache;

use Minds\Core\Di\Di;
use NotImplementedException;
use Psr\SimpleCache\CacheInterface;

class PsrWrapper implements CacheInterface
{
    /** @var boolean - whether tenant prefix should be used. */
    protected $useTenantPrefix = true;
    
    /** @var abstractCacher */
    protected $cache;

    /**
     * PsrWrapper constructor.
     * @param abstractCacher $cache
     */
    public function __construct(
        $cache = null
    ) {
        $this->cache = $cache ?: Di::_()->get('Cache');
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        return $this->cache->get($key) ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->cache->set($key, $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        return $this->cache->destroy($key);
    }

    /**
     * @inheritDoc
     * @throws NotImplementedException
     */
    public function clear()
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->cache->get($key) ?? $default;
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->cache->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->cache->destroy($key);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        return $this->cache->get($key) !== null;
    }
    
    /**
     * Allows specification of whether cache entries should have a tenant_id scoped prefix.
     * @param boolean $useTenantPrefix - whether tenant prefix should be used.
     * @return PsrWrapper
     */
    public function withTenantPrefix(bool $useTenantPrefix): PsrWrapper
    {
        $instance = clone $this;
        if ($this->cache instanceof Redis) {
            $instance->cache = $this->cache->withTenantPrefix($useTenantPrefix);
        }
        return $instance;
    }
}
