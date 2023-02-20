<?php
declare(strict_types=1);

namespace Minds\Core\Monetization\Demonetization\Validators;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Settings\Manager as UserSettingsManager;
use Minds\Core\Settings\Exceptions\UserSettingsNotFoundException;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

/**
 * Validate demonetization status in relation to Minds+.
 */
class DemonetizedPlusValidator
{
    public function __construct(
        private ?Config $config = null,
        private ?UserSettingsManager $userSettingsManager = null
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->userSettingsManager ??= Di::_()->get('Settings\Manager');
    }

    /**
     * Validate that either the given URN is not a Minds+ URN or it is, and the user is
     * allowed to post to Minds+.
     * @param string $urn - urn of post to check.
     * @param User $user - user that is proposing the post.
     * @throws UserErrorException if not permitted.
     * @return bool true if permitted
     */
    public function validateUrn(string $urn, User $user): bool
    {
        $plusTierUrn = $this->config->get('plus')['support_tier_urn'];

        if ($urn === $plusTierUrn) {
            try {
                $settings = $this->userSettingsManager
                    ->setUser($user)
                    ->getUserSettings();
                if ($settings->isPlusDemonetized()) {
                    throw new UserErrorException('Your Plus account is demonetized and cannot post');
                }
            } catch (UserSettingsNotFoundException $e) {
                return true;
            }
        }
        return true;
    }
}
