<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Entities\User;

/**
 * Feed notice for email verification.
 */
class VerifyEmailNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'top';

    // notice key / identifier.
    private const KEY = 'verify-email';

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
     * Whether notice should show in feed, based on whether user is trusted.
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        return !$user->isTrusted();
    }
}
