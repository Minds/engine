<?php

declare(strict_types=1);

namespace Minds\Core\Settings;

use Minds\Core\Di\Di;
use Minds\Core\Settings\Exceptions\UserSettingsNotFoundException;
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
     * @throws UserSettingsNotFoundException
     * @throws ServerErrorException
     */
    public function getUserSettings(): UserSettings
    {
        return $this->repository->getUserSettings($this->user->getGuid());
    }

    /**
     * @param array $data
     * @return bool
     * @throws ServerErrorException
     * @throws UserSettingsNotFoundException
     */
    public function storeUserSettings(array $data): bool
    {
        try {
            $settings = $this->getUserSettings();
            $settings->overrideWithData($data);
        } catch (UserSettingsNotFoundException $e) {
            if (!isset($data['user_guid'])) {
                $data['user_guid'] = $this->user->getGuid();
            }
            $settings = UserSettings::fromData($data);
        }

        return $this->repository->storeUserSettings($settings);
    }
}
