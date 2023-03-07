<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Settings;

use Minds\Core\Di\Di;
use Minds\Core\Boost\V3\Settings\Models\Settings;
use Minds\Core\Boost\V3\Settings\Repositories\RepositoryInterface;
use Minds\Entities\User;

class Manager
{
    /** @var User instance user */
    private User $user;

    public function __construct(
        private ?RepositoryInterface $settingsRepository = null
    ) {
        $this->settingsRepository ??= Di::_()->get('Boost\V3\Settings\Repository');
    }

    /**
     * Set instance user.
     * @param User $user - user to set.
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get settings from repository for instance user.
     * Will return default settings if none exist.
     * @return Settings - user settings.
     */
    public function getSettings(): Settings
    {
        return $this->settingsRepository->get($this->user);
    }

    /**
     * Update settings for instance user.
     * @param array $settings - array containing settings to update.
     * @return boolean true if settings were updated.
     */
    public function updateSettings(array $settings): bool
    {
        $currentSettings = $this->getSettings();

        if (isset($settings['boost_partner_suitability'])) {
            $currentSettings->setBoostPartnerSuitability($settings['boost_partner_suitability']);
        }

        return $this->settingsRepository->update($this->user, $currentSettings);
    }
}
