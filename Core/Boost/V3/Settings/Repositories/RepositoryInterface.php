<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Settings\Repositories;

use Minds\Core\Boost\V3\Settings\Models\Settings;
use Minds\Entities\User;

/**
 * Interface for a Boost settings repository.
 */
interface RepositoryInterface
{
    /**
     * Get settings for a given user.
     * @param User $user - given user.
     * @return Settings - user settings.
     */
    public function get(User $user): ?Settings;

    /**
     * Update settings for a given user.
     * @param User $user - given user.
     * @param Settings $settings - settings to update to.
     * @return bool true if update was successful.
     */
    public function update(User $user, Settings $settings): bool;

    /**
     * Insert settings for a given user.
     * @param User $user - given user.
     * @param Settings $settings - settings to insert for user.
     * @return bool true if insert was successful.
     */
    public function insert(User $user, Settings $settings): bool;
}
