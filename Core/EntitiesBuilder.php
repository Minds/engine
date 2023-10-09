<?php


namespace Minds\Core;

use Minds\Core\Data;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Repositories\EntitiesRepositoryInterface;
use Minds\Core\Entities\Repositories\MySQLRepository;
use Minds\Entities\Entity;
use Minds\Entities\EntityInterface;
use Minds\Entities\Factory;
use Minds\Entities\User;

class EntitiesBuilder
{
    public function __construct(
        protected ?Data\lookup $lookup = null,
        protected ?Entities\Resolver $urnResolver = null,
        protected ?EntitiesRepositoryInterface $entitiesRepository = null,
    ) {
        $this->lookup ??= Di::_()->get('Database\Cassandra\Data\Lookup');
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
        return $this->getEntitiesRepository()->loadFromIndex('username', strtolower($key));
        
        // $values = $this->lookup->get(strtolower($key));

        // $userGuid = key($values);
        // $user = $this->single($userGuid);

        // if ($user && $user instanceof User) {
        //     return $user;
        // }

        // return null;
    }

    public function getByUrn(string $urn): ?EntityInterface
    {
        $entity = $this->getUrnResolver()->single($urn);
        if (!$entity instanceof EntityInterface) {
            return null;
        }
        return $entity;
    }

    /**
     * Get entities
     * @param  array  $options
     * @return array
     */
    public function get(array $options = [])
    {
        $entitiesRepository = $this->getEntitiesRepository();
        if ($entitiesRepository instanceof MySQLRepository) {
            if ($options['guids'] ?? null) {
                return $this->entitiesRepository->loadFromGuid($options['guids']);
            }
        } else {
            return \elgg_get_entities($options);
        }
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

    private function getUrnResolver(): Entities\Resolver
    {
        return $this->urnResolver ??= Di::_()->get(Entities\Resolver::class);
    }

    private function getEntitiesRepository(): EntitiesRepositoryInterface
    {
        return $this->entitiesRepository ??= Di::_()->get(EntitiesRepositoryInterface::class);
    }
}
