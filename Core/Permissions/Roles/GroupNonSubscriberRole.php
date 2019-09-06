<?php

namespace Minds\Core\Permissions\Roles;

class GroupNonSubscriberRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_GROUP_NON_SUBSCRIBER);
    }
}
