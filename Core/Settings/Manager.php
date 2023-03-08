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
    public function getUserSettings(bool $allowEmpty = false): UserSettings
    {
        try {
            return $this->repository
            ->getUserSettings($this->user->getGuid())
            ->withUser($this->user);
        } catch (
            UserSettingsNotFoundException $e
        ) {
            if (!$allowEmpty) {
                throw $e;
            }
            return (new UserSettings())
                ->withUser($this->user);
        }
    }

    /**
     * @param array $data
     * @return bool
     */
    public function storeUserSettings(array $data): bool
    {
        $settings = (new UserSettings())
            ->withUser($this->user)
            ->withData($data);

        return $this->repository->storeUserSettings($settings);
    }
}
