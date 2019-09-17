<?php

namespace Minds\Core\Permissions\Roles;

class AdminRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_ADMIN);
        $this->addPermission(Roles::FLAG_APPOINT_ADMIN);
    }
}
