<?php

namespace Minds\Core\Permissions\Roles;

class BannedRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_BANNED);
    }
}
