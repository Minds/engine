<?php

namespace Minds\Core\Permissions\Delegates;

use Minds\Core\Permissions\Roles\Roles;
use Minds\Entities\User;

abstract class BaseRoleCalculator
{
    /** @var Roles */
    protected $roles;
    /** @var User */
    protected $user;

    public function __construct(User $user = null, Roles $roles = null)
    {
        $this->roles = $roles ?: new Roles();
        $this->user = $user;
    }

    abstract public function calculate($entity);
}
