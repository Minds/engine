<?php

namespace Minds\Core\Permissions\Roles;

class GroupOwnerRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_GROUP_OWNER);
        $this->addPermission(Flags::FLAG_APPOINT_ADMIN);
        $this->addPermission(Flags::FLAG_CREATE_POST);
        $this->addPermission(Flags::FLAG_EDIT_POST);
        $this->addPermission(Flags::FLAG_DELETE_POST);
        $this->addPermission(Flags::FLAG_APPOINT_MODERATOR);
        $this->addPermission(Flags::FLAG_APPROVE_SUBSCRIBER);
        $this->addPermission(Flags::FLAG_SUBSCRIBE);
        $this->addPermission(Flags::FLAG_VIEW);
        $this->addPermission(Flags::FLAG_VOTE);
        $this->addPermission(Flags::FLAG_CREATE_COMMENT);
        $this->addPermission(Flags::FLAG_EDIT_COMMENT);
        $this->addPermission(Flags::FLAG_DELETE_COMMENT);
        $this->addPermission(Flags::FLAG_REMIND);
        $this->addPermission(Flags::FLAG_WIRE);
        $this->addPermission(Flags::FLAG_MESSAGE);
        $this->addPermission(Flags::FLAG_INVITE);
        $this->addPermission(Flags::FLAG_CREATE_GROUP);
        $this->addPermission(Flags::FLAG_EDIT_GROUP);
        $this->addPermission(Flags::FLAG_DELETE_GROUP);
        $this->addPermission(Flags::FLAG_JOIN);
        $this->addPermission(Flags::FLAG_JOIN_GATHERING);
    }
}
