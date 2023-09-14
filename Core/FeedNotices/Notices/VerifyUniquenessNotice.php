<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Core\Di\Di;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Rewards\Eligibility\Manager as EligibilityManager;
use Minds\Entities\User;

/**
 * Feed notice to prompt a user to join rewards and verify their uniqueness.
 */
class VerifyUniquenessNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'inline';

    // notice key / identifier.
    private const KEY = 'verify-uniqueness';

    public function __construct(
        private ?EligibilityManager $eligibilityManager = null,
        private ?ExperimentsManager $experimentsManager = null
    ) {
        $this->eligibilityManager ??= Di::_()->get('Rewards\Eligibility\Manager');
        $this->experimentsManager ??= Di::_()->get('Experiments\Manager');
    }

    /**
     * Get location of notice in feed.
     * @return string location of notice in feed.
     */
    public function getLocation(): string
    {
        return self::LOCATION;
    }

    /**
     * Get notice key (identifier for notice).
     * @return string notice key.
     */
    public function getKey(): string
    {
        return self::KEY;
    }

    /**
     * Whether notice is dismissible.
     * @return boolean - true if notice is dismissible.
     */
    public function isDismissible(): bool
    {
        return true;
    }

    /**
     * Whether notice should show in feed, based on whether user has
     * no stored phone number hash and meets other prerequisites.
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        return !$user->getPhoneNumberHash() &&
            $this->isEligibleForRewards($user) &&
            !$this->experimentsManager->setUser($user)->isOn('epic-275-in-app-verification');
    }

    /**
     * Whether user is eligible to register for rewards.
     * @param User $user - user to check.
     * @return boolean true if user is eligible to register for rewards.
     */
    private function isEligibleForRewards(User $user): bool
    {
        return $this->eligibilityManager->setUser($user)
            ->isEligible();
    }
}
