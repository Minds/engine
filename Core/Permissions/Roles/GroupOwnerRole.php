<?php

namespace Minds\Core\Permissions\Roles;

class GroupOwnerRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_GROUP_OWNER);
    }
}
