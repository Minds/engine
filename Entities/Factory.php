<?php
namespace Minds\Entities;

use Minds\Core;
use Minds\Core\Data;

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
        $options = array_merge([ 'cache'=> true ], $options);

        $entity = null;
        $canBeCached = false;

        if (Core\Luid::isValid($value)) {
            if ($options['cache'] && isset(self::$entitiesCache[$value])) {
                return self::$entitiesCache[$value];
            }

            $canBeCached = true;

            $luid = new Core\Luid($value);
            $entity = Core\Events\Dispatcher::trigger('entity:resolve', $luid->getType(), [
                'luid' => $luid
            ], null);
        } else if (is_numeric($value)) {
            if ($options['cache'] && isset(self::$entitiesCache[$value])) {
                return self::$entitiesCache[$value];
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

        return $entity;
    }
}
