<?php

namespace Minds\Core\Permissions\Roles;

class ChannelModeratorRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_CHANNEL_MODERATOR);
    }
}
