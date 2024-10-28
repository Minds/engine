<?php
namespace Minds\Entities;

use Minds\Core;
use Minds\Core\Data;
use Minds\Core\Data\cache\InMemoryCache;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Repositories\EntitiesRepositoryInterface;

/**
 * Entities Factory
 */
class Factory
{
    /**
     * Build an entity based an GUID, an array or an object
     * @param  mixed  $value
     * @param  array  $options - ['cache' => bool]
     * @return Entity
     */
    public static function build($value, array $options = [])
    {
        $options = array_merge([ 'cache'=> true, 'cacheTtl' => -1 ], $options);
        $inMemoryCache = Di::_()->get(InMemoryCache::class);

        $entity = null;
        $canBeCached = false;
        $psrCache = Di::_()->get('Cache\PsrWrapper');
        $cacheKey = null;

        if (Core\Luid::isValid($value)) {
            $cacheKey = "entity:" . $value;
            if ($options['cache'] && $inMemoryCache->has($cacheKey)) {
                return $inMemoryCache->get($cacheKey);
            }

            $canBeCached = true;

            $luid = new Core\Luid($value);
            $entity = Core\Events\Dispatcher::trigger('entity:resolve', $luid->getType(), [
                'luid' => $luid
            ], null);
        } elseif (is_numeric($value)) {
            $cacheKey = "entity:" . $value;

            if ($options['cache'] && $cacheKey && $inMemoryCache->has($cacheKey)) {
                return $inMemoryCache->get($cacheKey);
            }

            if ($options['cache'] && $options['cacheTtl'] > 0) {
                $cached = $psrCache->get($cacheKey);
                if ($cached) {
                    return unserialize($cached);
                }
            }

            $canBeCached = true;

            /** @var EntitiesRepositoryInterface */
            $entitiesRepository = Di::_()->get(EntitiesRepositoryInterface::class);
            $entity = $entitiesRepository->loadFromGuid((int) $value);
        } elseif (is_object($value) || is_array($value)) {
            // @todo Check if we can just read ->guid and if not empty we'll load from cache
            $row = $value;
        } elseif (is_string($value)) {
            // @todo Check if we can just read ->guid and if not empty we'll load from cache
            $row = json_decode($value, true);
        } else {
            return false;
        }

        if (!$entity && isset($row)) {
            $entity = Core\Di\Di::_()->get('Entities')->build((object) $row);
        }

        // filter out invalid users
        if ($entity instanceof User && !$entity->getUsername()) {
            return false;
        };

        if ($options['cache'] && $canBeCached && $entity && $cacheKey) {
            $inMemoryCache->set($cacheKey, $entity);
        }

        if ($options['cache'] && $options['cacheTtl'] > 0 && $cacheKey) {
            $psrCache->set($cacheKey, serialize($entity), $options['cacheTtl']);
        }

        return $entity;
    }

    /**
     * Caches an entity (currently just in memory)
     */
    public static function cache(EntityInterface $entity): void
    {
        $guid = $entity->getGuid();

        $cacheKey = "entity:" . $guid;

        $inMemoryCache = Di::_()->get(InMemoryCache::class);
        $inMemoryCache->set($cacheKey, $entity);
    }

    /**
     * Invalidates an entity cache.
     * @param EntityInterface $entity - entity to invalidate cache entry for.
     * @return void
     */
    public static function invalidateCache(EntityInterface $entity): void
    {
        $guid = $entity?->getGuid();

        if (!$guid) {
            return;
        }

        self::invalidateCacheByGuid($guid);
    }

    /**
     * Caches an entity by guid.
     * @param int|string $guid - entity GUID.
     * @return void
     */
    public static function invalidateCacheByGuid(int|string $guid): void
    {
        $cacheKey = "entity:" . $guid;

        $inMemoryCache = Di::_()->get(InMemoryCache::class);
        $inMemoryCache->delete($cacheKey);

        $psrCache = Di::_()->get('Cache\PsrWrapper');
        $psrCache->delete($cacheKey);
    }
}
