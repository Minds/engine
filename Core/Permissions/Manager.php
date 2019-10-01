<?php

namespace Minds\Core\Permissions;

use Minds\Core\Di\Di;
use Minds\Core\Permissions\Permissions;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Permissions\Roles\Roles;

/*
* Manager for managing role based permissions
*/

class Manager
{
    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct($entitiesBuilder = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * Takes a user_guid and list of entity guids
     * Builds up a permissions object
     * Permissions contains the user's role per entity, channel and group.
     *
     * @param array $opts
     *                    - user_guid: long, the user's guid for calculating permissions
     *                    - guids: array long, the list of entities to permit
     *
     * @return Permissions A map of channels, groups and entities with the user's role for each
     */
    public function getList(array $opts = []): Permissions
    {
        $opts = array_merge([
            'user_guid' => null,
            'guids' => [],
            'entities' => [],
        ], $opts);

        if ($opts['user_guid'] === null) {
            throw new \InvalidArgumentException('user_guid is required');
        }

        $guids = $opts['guids'] ?: array_map(function ($item) {
            return $item['guid'];
        }, $opts['entities']);

        $user = $this->entitiesBuilder->single($opts['user_guid']);
        $entities = $this->entitiesBuilder->get(['guids' => $guids]);

        if (!$user) {
            throw new \InvalidArgumentException('User does not exist');
        }

        if ($user && $user->getType() !== 'user') {
            throw new \InvalidArgumentException('Entity is not a user');
        }

        $entities = array_merge($entities, $opts['entities']);

        $roles = new Roles();

        /** @var Permissions */
        $permissions = new Permissions($user, $roles, $this->entitiesBuilder);
        if (is_array($entities)) {
            $permissions->calculate($entities);
        }

        return $permissions;
    }
}
