<?php
namespace Minds\Core\Data\cache;

use Psr\SimpleCache\CacheInterface;

class InMemoryCache implements CacheInterface
{
    private $kvCache = [];

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        return $kvCache[$key] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
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
