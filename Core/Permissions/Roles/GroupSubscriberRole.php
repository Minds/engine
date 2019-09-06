<?php

namespace Minds\Core\Permissions\Roles;

class GroupSubscriberRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_GROUP_SUBSCRIBER);
    }
}
