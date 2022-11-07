<?php

namespace Minds\Core\Settings;

use Minds\Core\Di\Di;
use Minds\Core\Settings\Models\UserSettings;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Traits\MagicAttributes;

/**
 * @method self setUser(User $user)
 */
class Manager
{
    use MagicAttributes;

    private ?User $user = null;

    public function __construct(
        private ?Repository $repository = null
    ) {
        $this->repository ??= Di::_()->get('Settings\Repository');
    }

    /**
     * @return UserSettings
     * @throws Exceptions\UserSettingsNotFoundException
     * @throws ServerErrorException
     */
    public function getUserSettings(): UserSettings
    {
        return $this->repository->getUserSettings($this->user->getGuid());
    }
}
