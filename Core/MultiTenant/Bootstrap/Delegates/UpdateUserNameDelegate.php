<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\Entities\Actions\Save;
use Minds\Core\Log\Logger;
use Minds\Core\Security\ACL;
use Minds\Entities\User;

/**
 * Delegate for updating the user's name.
 */
class UpdateUserNameDelegate
{
    public function __construct(
        private Save $saveAction,
        private Logger $logger,
        private ACL $acl
    ) {
    }

    /**
     * Update the user's name.
     * @param User $user - The user to update.
     * @param string $name - The new name.
     * @return void
     */
    public function onUpdate(User $user, string $name)
    {
        $ignore = $this->acl::$ignore;
        $this->acl::$ignore = true;

        $user->setName($name);
        $this->saveAction->setEntity($user)->save(true);

        $this->acl::$ignore = $ignore;
    }
}
