<?php

namespace Minds\Core\Permissions\Roles;

class ChannelSubscriberRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_CHANNEL_SUBSCRIBER);
    }
}
