<?php
namespace Minds\Core\Data\cache;

use Psr\SimpleCache\CacheInterface;

/**
 * This cache is persitent for each request/run
 * RoadRunner (start.rr.php) will clear this cache on every request
 */
class InMemoryCache implements CacheInterface
{
    /** @var int */
    const MAX_LOCAL_CACHE = 1000;

    protected $kvCache = [];

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        return isset($this->kvCache[$key]) ? $this->kvCache[$key] : $default;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
        if (count($this->kvCache) > static::MAX_LOCAL_CACHE) {
            $this->clear();
        }

        $this->kvCache[$key] = $value;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        unset($this->kvCache[$key]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->kvCache = [];
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->kvCache[$key] ?? $default;
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->kvCache[$key] = $value;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            unset($this->kvCache[$key]);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        return isset($this->kvCache[$key]);
    }
}
