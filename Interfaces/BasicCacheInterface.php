<?php
namespace Minds\Interfaces;

/**
 * Interface for a basic cache.
 */
interface BasicCacheInterface
{
    /**
     * Get from the cache.
     */
    public function get();

    /**
     * Set value in the cache.
     */
    public function set($value);
}
