<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Settings\Repositories;

use Minds\Core\Supermind\Settings\Models\Settings;
use Minds\Entities\User;

/**
 * Interface for a Supermind settings repository.
 */
interface RepositoryInterface
{
    /**
     * Get settings for a given user.
     * @throws SettingsNotFoundException - when settings are not found.
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
