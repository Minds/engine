<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\SocialCompass\Manager as SocialCompassManager;

/**
 * Feed notice for build your algorithm.
 */
class BuildYourAlgorithmNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'inline';

    // notice key / identifier.
    private const KEY = 'build-your-algorithm';

    /**
     * Constructor.
     * @param ?SocialCompassManager $socialCompassManager - manager for social compass.
     */
    public function __construct(
        private ?SocialCompassManager $socialCompassManager = null
    ) {
        $this->socialCompassManager ??= Di::_()->get('SocialCompass\Manager');
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
     * previously answered build your algorithm.
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        $count = $this->socialCompassManager
            ->setUser($user)
            ->countAnswers();

        return $count < 1;
    }
}
