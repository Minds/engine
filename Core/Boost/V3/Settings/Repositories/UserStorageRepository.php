<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Settings\Repositories;

use Minds\Entities\User;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Boost\V3\Settings\Models\Settings;

/**
 * UserStorageRepository that handles boost_settings as JSON strings attached to a User entity.
 */
class UserStorageRepository implements RepositoryInterface
{
    public function __construct(
        private ?Save $saveAction = null
    ) {
        $this->saveAction ??= new Save();
    }

    /**
     * Get settings for a given user.
     * @param User $user - given user.
     * @return Settings - user settings.
     */
    public function get(User $user): ?Settings
    {
        $boostSettings = $user->getBoostSettings();

        // will return defaults if no settings are found.
        return (new Settings(
            boostPartnerSuitability: $boostSettings['boost_partner_suitability'] ?? null,
        ));
    }

    /**
     * Update settings for a given user.
     * @param User $user - given user.
     * @param Settings $settings - settings to update to.
     * @return bool true if update was successful.
     */
    public function update(User $user, Settings $settings): bool
    {
        return $this->upsert($user, $settings);
    }

    /**
     * Insert settings for a given user.
     * @param User $user - given user.
     * @param Settings $settings - settings to insert for user.
     * @return bool true if insert was successful.
     */
    public function insert(User $user, Settings $settings): bool
    {
        return $this->upsert($user, $settings);
    }

    /**
     * Upsert settings for a given user.
     * @param User $user - given user.
     * @param Settings $settings - settings to upsert.
     * @return bool true if upsert was successful.
     */
    private function upsert(User $user, Settings $settings): bool
    {
        $user->setBoostSettings(json_encode($settings));
        return (bool) $this->saveAction->setEntity($user)->save();
    }
}
