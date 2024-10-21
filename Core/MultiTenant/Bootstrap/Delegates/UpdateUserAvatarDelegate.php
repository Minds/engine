<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\Channels\AvatarService;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\Log\Logger;
use Minds\Core\Security\ACL;
use Minds\Entities\User;

/**
 * Delegate for updating the users avatar.
 */
class UpdateUserAvatarDelegate
{
    public function __construct(
        private AvatarService $avatarService,
        private Logger $logger,
        private SaveAction $saveAction,
        private ACL $acl
    ) {
    }

    /**
     * Updates the users avatar.
     * @param User $user - The user to update the avatar for.
     * @param string|null $imageBlob - The raw image blob to update the avatar with.
     * @return bool - True if the avatar was updated.
     */
    public function onUpdate(
        User $user,
        string $imageBlob = null
    ): bool {
        $previousAclIgnoreState = $this->acl::$ignore;
        $this->acl::$ignore = true;

        $success = $this->avatarService->withUser($user)->createFromBlob($imageBlob);

        $user->icontime = time();
        $this->saveAction->setEntity($user)
            ->withMutatedAttributes(['icontime'])
            ->save();

        $this->acl::$ignore = $previousAclIgnoreState;

        return $success;
    }
}
