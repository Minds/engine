<?php

namespace Minds\Core\Permissions\Roles;

use Zend\Permissions\Rbac;
use  Minds\Core\Permissions\Roles\Role;

abstract class BaseRole extends Rbac\Role implements \JsonSerializable, Role
{
    public function export(): array
    {
        $export = [];
        $export['name'] = $this->getName();
        $export['permissions'] = $this->getPermissions();

        return $export;
    }

    public function jsonSerialize(): array
    {
        return $this->export();
    }
}
