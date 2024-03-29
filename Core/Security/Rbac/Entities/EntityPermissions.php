<?php

namespace Minds\Core\Security\Rbac\Entities;

use Minds\Traits\MagicAttributes;

/**
* Class Permissions
* @method Permissions setAllowComments(bool $allowComments)
* @method bool getAllowComments();
*/
class EntityPermissions
{
    use MagicAttributes;

    /** @var bool AllowComments */
    private $allowComments = true;
}
