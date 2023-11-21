<?php
namespace Minds\Core\Security\Rbac\Exceptions;

use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;

class RbacNotAllowed extends ForbiddenException
{
    public function __construct(PermissionsEnum $permission)
    {
        $this->message = "Fordidden: {$permission->name} is not assigned";
    }
}
