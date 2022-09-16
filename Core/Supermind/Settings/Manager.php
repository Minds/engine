<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Settings;

use Minds\Core\Di\Di;
use Minds\Core\Supermind\Settings\Exceptions\SettingsNotFoundException;
use Minds\Core\Supermind\Settings\Models\Settings;
use Minds\Core\Supermind\Settings\Repositories\RepositoryInterface;
use Minds\Core\Supermind\Settings\Validators\SupermindUpdateSettingsRequestValidator;
use Minds\Entities\User;

class Manager
{
    /** @var User instance user */
    private User $user;

    public function __construct(
        private ?RepositoryInterface $manager = null,
        private ?SupermindUpdateSettingsRequestValidator $getValidator = null
    ) {
        $this->settingsRepository ??= Di::_()->get('Supermind\Settings\Repository');
        $this->getValidator ??= new SupermindUpdateSettingsRequestValidator();
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
        try {
            return $this->settingsRepository->get($this->user);
        } catch (SettingsNotFoundException $e) {
            return new Settings();
        }
    }

    /**
     * Update settings for instance user.
     * @param array $settings - array containing settings to update.
     * @return boolean true if settings were updated.
     */
    public function updateSettings(array $settings): bool
    {
        $currentSettings = $this->getSettings();

        if (isset($settings['min_offchain_tokens'])) {
            $currentSettings->setMinOffchainTokens($settings['min_offchain_tokens']);
        }

        if (isset($settings['min_cash'])) {
            $currentSettings->setMinCash($settings['min_cash']);
        }

        return $this->settingsRepository->update($this->user, $currentSettings);
    }
}
