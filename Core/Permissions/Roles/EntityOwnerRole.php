<?php

namespace Minds\Core\Permissions\Roles;

class EntityOwnerRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_ENTITY_OWNER);
        $this->addPermission(Flags::FLAG_EDIT_POST);
        $this->addPermission(Flags::FLAG_DELETE_POST);
        $this->addPermission(Flags::FLAG_VIEW);
        $this->addPermission(Flags::FLAG_CREATE_COMMENT);
        $this->addPermission(Flags::FLAG_EDIT_COMMENT);
        $this->addPermission(Flags::FLAG_DELETE_COMMENT);
        $this->addPermission(Flags::FLAG_VOTE);
        $this->addPermission(Flags::FLAG_REMIND);
        $this->addPermission(Flags::FLAG_WIRE);
    }
}
