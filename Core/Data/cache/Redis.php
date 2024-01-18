<?php
/**
 * Redis cacher.
 *
 * @author Mark Harding
 */

namespace Minds\Core\Data\cache;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use NotImplementedException;
use Psr\SimpleCache\CacheInterface;
use Redis as RedisServer;

class Redis extends abstractCacher implements CacheInterface
{
    private $redisMaster;
    private $redisSlave;

    /** @var Config */
    private $config;

    /** @var boolean - whether tenant prefix should be used. */
    private $useTenantPrefix = true;

    public function __construct(
        $config = null,
        private ?InMemoryCache $inMemoryCache = null,
    ) {
        $this->config = $config ?? Di::_()->get('Config');
        $this->inMemoryCache ??= Di::_()->get(InMemoryCache::class);
    }

    private function getMaster()
    {
        if (!$this->redisMaster) {
            $this->redisMaster = new RedisServer();

            // TODO fully move to Redis HA
            $redisHa = ($this->config->get('redis')['ha']) ?? null;
            if ($redisHa) {
                $master = ($this->config->get('redis')['master']['host']) ?? null;
                $masterPort = ($this->config->get('redis')['master']['port']) ?? null;
                $this->redisMaster->connect($master, $masterPort, 0.5);
            } else {
                $this->redisMaster->connect($this->config->get('redis')['master']);
            }
        }

        return $this->redisMaster;
    }

    private function getSlave()
    {
        if (!$this->redisSlave) {
            $this->redisSlave = new RedisServer();

            // TODO fully move to Redis HAs
            $redisHa = ($this->config->get('redis')['ha']) ?? null;
            if ($redisHa) {
                $slave = ($this->config->get('redis')['slave']['host']) ?? null;
                $slavePort = ($this->config->get('redis')['slave']['port']) ?? null;
                $this->redisSlave->connect($slave, $slavePort, 0.5);
            } else {
                $this->redisSlave->connect($this->config->get('redis')['slave']);
            }
        }

        return $this->redisSlave;
    }

    public function get($key, $default = null)
    {
        if ($this->inMemoryCache->has($key)) {
            return $this->inMemoryCache->get($key);
        }

        $key = $this->buildKey($key);

        try {
            $redis = $this->getSlave();
            $value = $redis->get($key);
            if ($value !== false) {
                $value = json_decode($value, true);
                if (is_numeric($value)) {
                    $this->inMemoryCache->set($key, (int) $value);
                    
                    return (int) $value;
                }
                $this->inMemoryCache->set($key, $value);

                return $value;
            }
        } catch (\Exception $e) {
            //error_log("could not read redis $this->slave");
            //error_log($e->getMessage());
        }

        return false;
    }

    public function set($key, $value, $ttl = 0)
    {
        $key = $this->buildKey($key);

        //error_log("still setting $key with value $value for $ttl seconds");
        try {
            $redis = $this->getMaster();
            if ($ttl) {
                $redis->set($key, json_encode($value), ['ex' => $ttl]);
            } else {
                $redis->set($key, json_encode($value));
            }
            return true;
        } catch (\Exception $e) {
            //error_log("could not write ($key) to redis $this->master");
            //error_log($e->getMessage());
        }
        return false;
    }

    /** Iterate over redis keys that return a cursor
     *  iterator is passed by reference so you can paginate and get returned values
     */
    public function scan(&$iterator, $pattern = null)
    {
        try {
            $redis = $this->getSlave();
            return  $redis->scan($iterator, $pattern);
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
    }

    public function destroy($key)
    {
        if ($this->inMemoryCache->has($key)) {
            $this->inMemoryCache->delete($key);
        }

        $key = $this->buildKey($key);

        try {
            $redis = $this->getMaster();
            $redis->delete($key);
        } catch (\Exception $e) {
            //error_log("could not delete ($key) from redis $this->master");
        }
    }

    /**
     * @return RedisServer
     */
    public function forReading()
    {
        return $this->getSlave() ?: $this->getMaster();
    }

    /**
     * @return RedisServer
     */
    public function forWriting()
    {
        return $this->getMaster();
    }

    public function __destruct()
    {
        try {
            if ($this->redisSlave) {
                $this->redisSlave->close();
            }
            if ($this->redisMaster) {
                $this->redisMaster->close();
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * Allows specification on whether cache entries should have a tenant_id scoped prefix.
     * @param boolean $useTenantPrefix - whether tenant prefix should be used.
     * @return self
     */
    public function withTenantPrefix(bool $tenantPrefix): self
    {
        $instance = clone $this;
        $instance->useTenantPrefix = $tenantPrefix;
        return $instance;
    }

    /**
     * Build full cache key - prefixing it with tenant prefix if the cache is to be scoped by tenant ID and one is set.     *
     * @param string $key - initial key.
     * @return string - resulting key.
     */
    private function buildKey(string $key): string
    {
        if ($this->useTenantPrefix && $tenantId = $this->config->get('tenant_id')) {
            $key = "tenant:$tenantId:$key";
        }
        return $key;
    }

    public function delete($key)
    {
        throw new NotImplementedException();
    }

    public function clear()
    {
        throw new NotImplementedException();
    }

    public function getMultiple($keys, $default = null)
    {
        throw new NotImplementedException();
    }

    public function setMultiple($values, $ttl = null)
    {
        throw new NotImplementedException();
    }

    public function deleteMultiple($keys)
    {
        throw new NotImplementedException();
    }

    public function has($key): bool
    {
        return (bool) $this->get($key);
    }
}
