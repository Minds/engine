<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Feeds\User\Manager as FeedsUserManager;

/**
 * Feed notice to prompt a user to connect their wallet.
 */
class BoostLatestPostNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'inline';

    // notice key / identifier.
    private const KEY = 'boost-latest-post';

    public function __construct(
        private ?FeedsUserManager $feedsUserManager = null,
    ) {
        $this->feedsUserManager ??= Di::_()->get('Feeds\User\Manager');
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
     * Whether notice should show in feed, based on whether user has
     * made a post that can be boosted
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        return $this->feedsUserManager->setUser($user)
            ->hasMadePosts();
    }
}
