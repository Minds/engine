<?php
namespace Minds\Entities;

use Minds\Core;
use Minds\Core\Data;
use Minds\Core\Di\Di;

/**
 * Entities Factory
 */
class Factory
{
    private static $entitiesCache = [];

    /**
     * Build an entity based an GUID, an array or an object
     * @param  mixed  $value
     * @param  array  $options - ['cache' => bool]
     * @return Entity
     */
    public static function build($value, array $options = [])
    {
        $options = array_merge([ 'cache'=> true, 'cacheTtl' => -1 ], $options);

        $entity = null;
        $canBeCached = false;
        $psrCache = Di::_()->get('Cache\PsrWrapper');
        $cacheKey = null;

        if (Core\Luid::isValid($value)) {
            $cacheKey = (string) $value;
            if ($options['cache'] && isset(self::$entitiesCache[$cacheKey])) {
                return self::$entitiesCache[$cacheKey];
            }

            $canBeCached = true;

            $luid = new Core\Luid($value);
            $entity = Core\Events\Dispatcher::trigger('entity:resolve', $luid->getType(), [
                'luid' => $luid
            ], null);
        } elseif (is_numeric($value)) {
            $cacheKey = (string) $value;

            if ($options['cache'] && $cacheKey && isset(self::$entitiesCache[$cacheKey])) {
                return self::$entitiesCache[$cacheKey];
            }

            if ($options['cache'] && $options['cacheTtl'] > 0) {
                $cached = $psrCache->get("entity:$cacheKey");
                if ($cached) {
                    return unserialize($cached);
                }
            }

            $canBeCached = true;

            $db = new Data\Call('entities');
            $row = $db->getRow($value);
            $row['guid'] = $value;
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
            self::$entitiesCache[$cacheKey] = $entity;
        }

        if ($options['cache'] && $options['cacheTtl'] > 0 && $cacheKey) {
            $psrCache->set("entity:$cacheKey", serialize($entity), $options['cacheTtl']);
        }

        return $entity;
    }
}
