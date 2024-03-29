<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Entities\User;

/**
 * Feed notice to encourage users to boost their channel.
 */
class BoostChannelNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'top';

    // notice key / identifier.
    private const KEY = 'boost-channel';


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
     * Whether notice should show in feed, based on whether user is trusted.
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        return !$this->isTenantContext() && $user->isTrusted();
    }
}
