<?php

namespace Minds\Core\Permissions\Roles;

use Zend\Permissions\Rbac;

abstract class BaseRole extends Rbac\Role implements \JsonSerializable
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
