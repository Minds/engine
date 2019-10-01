<?php

namespace Minds\Core\Permissions\Roles;

class LoggedOutClosedRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_LOGGED_OUT_CLOSED);
        //No permissions for closed channels or groups
    }
}
