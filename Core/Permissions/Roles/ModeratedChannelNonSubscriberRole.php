<?php

namespace Minds\Core\Permissions\Roles;

class ModeratedChannelNonSubscriberRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_MODERATED_CHANNEL_NON_SUBSCRIBER);
        $this->addPermission(Flags::FLAG_VIEW);
    }
}
