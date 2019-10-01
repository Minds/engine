<?php

namespace Minds\Core\Permissions\Roles;

class ClosedChannelNonSubscriberRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        //No permissions for closed channel non subscribers
    }
}
