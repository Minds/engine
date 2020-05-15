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

        if (Core\Luid::isValid($value)) {
            if ($options['cache'] && isset(self::$entitiesCache[$value])) {
                return self::$entitiesCache[$value];
            }

            $canBeCached = true;

            $luid = new Core\Luid($value);
            $entity = Core\Events\Dispatcher::trigger('entity:resolve', $luid->getType(), [
                'luid' => $luid
            ], null);
        } elseif (is_numeric($value)) {
            if ($options['cache'] && isset(self::$entitiesCache[$value])) {
                return self::$entitiesCache[$value];
            }

            if ($options['cache'] && $options['cacheTtl'] > 0) {
                $cached = $psrCache->get("entity:$value");
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

        if ($options['cache'] && $canBeCached && $entity) {
            self::$entitiesCache[$value] = $entity;
        }

        if ($options['cache'] && $options['cacheTtl'] > 0) {
            $psrCache->set("entity:$value", serialize($entity), $opts['cacheTtl']);
        }

        return $entity;
    }
}
