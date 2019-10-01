<?php

namespace Minds\Core\Permissions\Roles;

class ClosedGroupNonSubscriberRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_CLOSED_GROUP_NON_SUBSCRIBER);
        //No permissions for closed group non subscribers
    }
}
