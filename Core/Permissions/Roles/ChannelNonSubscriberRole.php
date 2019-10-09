<?php

namespace Minds\Core\Permissions\Roles;

class ChannelNonSubscriberRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
    }
}
