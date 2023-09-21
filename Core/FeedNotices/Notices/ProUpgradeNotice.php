<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Entities\User;

/**
 * Feed notice for upgrading to plus.
 */
class ProUpgradeNotice extends AbstractNotice
{
    // minimum age for an account to be shown the notice.
    private const MINIMUM_ACCOUNT_AGE = 2592000; // 30 days.

    // location of notice in feed.
    private const LOCATION = 'top';

    // notice key / identifier.
    private const KEY = 'pro-upgrade';

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
     * Whether notice should show in feed, based on whether user
     * is not already plus and the account has existed for
     * longer than the minimum account age.
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        return $user->isPlus() && (!$user->getProExpires() || $user->getProExpires() < time());
    }
}
