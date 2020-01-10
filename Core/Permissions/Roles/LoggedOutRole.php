<?php

namespace Minds\Core\Permissions\Roles;

use Zend\Permissions\Rbac;

class LoggedOutRole extends Rbac\Role
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_LOGGED_OUT);
    }
}
