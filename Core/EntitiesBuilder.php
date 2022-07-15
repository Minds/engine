<?php


namespace Minds\Core;

use Minds\Core\Data;
use Minds\Core\Di\Di;
use Minds\Entities\Entity;
use Minds\Entities\Factory;
use Minds\Entities\User;

class EntitiesBuilder
{
    /** @var Data\lookup */
    protected $lookup;

    public function __construct($lookup = null)
    {
        $this->lookup = $lookup ?? Di::_()->get('Database\Cassandra\Data\Lookup');
    }

    /**
     * Build by a single guid
     * @param $guid int|string
     * @param $opts array
     * @return Entity
     */
    public function single($guid, $opts = [])
    {
        return Factory::build($guid, $opts);
    }

    /**
     * Returns a user from a lookup key, (eg. a username)
     * @param string $key
     * @return User
     */
    public function getByUserByIndex(string $key): ?User
    {
        $values = $this->lookup->get(strtolower($key));

        $userGuid = key($values);
        $user = $this->single($userGuid);

        if ($user && $user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * Get entities
     * @param  array  $options
     * @return array
     */
    public function get(array $options = [])
    {
        return \elgg_get_entities($options);
    }

    /**
     * List entities
     * @param  array $options
     * @return array
     */
    public function view($options)
    {
        //	$options['count'] = NULL;
        return \elgg_list_entities($options);
    }

    /**
     * Builds an entity object based on the values passed (GUID, array, object, etc)
     * @param  mixed  $row
     * @param  bool   $cache - cache or load from cache?
     * @return Entity|null
     */
    public function build($row, $cache = true)
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

        //plugins should, strictly speaking, handle the routing of entities by themselves..
        if (($new_entity = Events\Dispatcher::trigger('entities:map', 'all', [ 'row' => $row ]))
            || $new_entity = elgg_trigger_plugin_hook('entities_class_loader', 'all', $row)) {
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
    public function buildNamespace(array $options)
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
