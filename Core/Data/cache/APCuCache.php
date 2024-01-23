<?php
namespace Minds\Core\Data\cache;

use Psr\SimpleCache\CacheInterface;

/**
 * PSR-6 APCu Cache
 */
class APCuCache implements CacheInterface
{
    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        $val = apcu_fetch($key, $ok);

        if (!$ok) {
            return $default;
        }
    
        return $val;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = 0)
    {
        $ok = apcu_store($key, $value, $ttl);
        return $ok;
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        return apcu_delete($key);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return apcu_clear_cache();
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        $values = [];

        foreach ($keys as $key) {
            $value = apcu_fetch($key, $ok);
            $values[$key] = $ok ? $value : $default;
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        $ok = apcu_exists($key);
        return $ok;
    }
}
