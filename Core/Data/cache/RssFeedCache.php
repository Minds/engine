<?php
declare(strict_types=1);

namespace Minds\Core\Data\cache;

use Laminas\Cache\Storage\StorageInterface;
use NotImplementedException;

class RssFeedCache implements StorageInterface
{
    public function __construct(
        private readonly Redis $cache
    ) {
    }
    /**
     * @inheritDoc
     */
    public function setOptions($options)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getOptions()
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getItem($key, &$success = null, &$casToken = null): mixed
    {
        $item = $this->cache->get($key);

        if (!$item) {
            $success = false;
            return null;
        }

        $success = true;
        return $item;
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys)
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key): bool
    {
        return !!$this->cache->get($key);
    }

    /**
     * @inheritDoc
     */
    public function hasItems(array $keys)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($key)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getMetadatas(array $keys)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function setItem($key, $value): void
    {
        $this->cache->set($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function setItems(array $keyValuePairs): void
    {
        foreach ($keyValuePairs as $key => $value) {
            $this->setItem($key, $value);
        }
    }

    /**
     * @inheritDoc
     */
    public function addItem($key, $value)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function addItems(array $keyValuePairs)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function replaceItem($key, $value)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function replaceItems(array $keyValuePairs)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function checkAndSetItem($token, $key, $value)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function touchItem($key)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function touchItems(array $keys)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function removeItem($key)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function removeItems(array $keys)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function incrementItem($key, $value)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function incrementItems(array $keyValuePairs)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function decrementItem($key, $value)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function decrementItems(array $keyValuePairs)
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getCapabilities()
    {
        throw new NotImplementedException();
    }
}
