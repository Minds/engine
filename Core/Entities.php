<?php
namespace Minds\Core;

use Minds\Core\Data;
use Minds\Entities\Entity;

/**
 * Minds Entities Controller
 */
class Entities extends base
{
    public function init()
    {
    }

    /**
     * Get entities
     * @param  array  $options
     * @return array
     */
    public static function get(array $options = [])
    {
        return \elgg_get_entities($options);
    }

    /**
     * Builds an entity object based on the values passed (GUID, array, object, etc)
     * @param  mixed  $row
     * @param  bool   $cache - cache or load from cache?
     * @return Entity|null
     */
    public static function build($row, $cache = true)
    {
        if (is_array($row)) {
            $row = (object) $row;
        }

        if (!is_object($row)) {
            return $row;
        }

        if (!isset($row->guid)) {
            return $row;
        }

        if (($new_entity = Events\Dispatcher::trigger('entities:map', 'all', [ 'row' => $row ]))) {
            return $new_entity;
        }

        if (isset($row->subtype) && $row->subtype) {
            $sub = "Minds\\Entities\\" . ucfirst($row->type) . "\\" . ucfirst($row->subtype);
            if (class_exists($sub) && is_subclass_of($sub, 'ElggEntity')) {
                return new $sub($row, $cache);
            } elseif (is_subclass_of($sub, "Minds\\Entities\\DenormalizedEntity") || is_subclass_of($sub, "Minds\\Entities\\NormalizedEntity")) {
                return (new $sub())->loadFromArray((array) $row);
            }
        }

        if (!property_exists($row, 'type') || !$row->type) {
            return null;
        }
        $default = "Minds\\Entities\\" . ucfirst($row->type);
        if (class_exists($default) && is_subclass_of($default, 'ElggEntity')) {
            return new $default($row, $cache);
        } elseif (is_subclass_of($default, "Minds\\Entities\\DenormalizedEntity") || is_subclass_of($default, "Minds\\Entities\\NormalizedEntity")) {
            return (new $default())->loadFromArray((array) $row);
        }

        return null;
    }

    /**
     * Builds an entity row key namespace based on static::get() options
     * @param  array  $options
     * @return string
     */
    public static function buildNamespace(array $options)
    {
        $options = $options + [
            'type' => null,
            'subtype' => null,
            'owner_guid' => null,
            'container_guid' => null,
            'network' => null,
        ];
        $namespace = $options['type'] ?: 'object';

        if ($options['subtype']) {
            $namespace .= ':' . $options['subtype'];
        }

        if ($options['owner_guid']) {
            $namespace .= ':user:' . $options['owner_guid'];
        }

        if ($options['container_guid']) {
            $namespace .= ':container:' . $options['container_guid'];
        }
        if ($options['network']) {
            $namespace .= ':network:' . $options['network'];
        }

        return $namespace;
    }
}
