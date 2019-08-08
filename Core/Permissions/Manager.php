<?php

namespace Minds\Core\Permissions;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Data\Call;
use Minds\Core\Permissions\Roles;

/*
* Manager for managing role based permissions
*/
class Manager {

    /** @var EntityBuilder */
    private $entityBuilder;
   
    public function __construct($entityBuilder = null) {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    public function getList(array $opts = []) {
        $opts = array_merge([
            'user_guid' => null,
            'guids' => []
        ], $opts);

        if ($opts['user_guid'] === null) {
            throw new \InvalidArgumentException('user_guid is required');
        }
       
        $user = $this->entitiesBuilder->single($opts['user_guid']);
        $entities = $this->entitiesBuilder->get($opts);
        error_log(var_export($user->getGroupMembership(), true));
        if ($user->getType() !== 'user') {
            throw new \InvalidArgumentException('Entity is not a user');
        }
        
        $permissions = new Permissions($user);
        if(is_array($entities)) {
            error_log('calculating');
            $permissions->calculate($entities);
        }
        return $permissions;
    }
}
