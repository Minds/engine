<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Core\Experiments\Manager as ExperimentsManager;

/**
 * Feed notice to prompt a user to set up their channel.
 */
class SetupChannelNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'inline';

    // notice key / identifier.
    private const KEY = 'setup-channel';

    public function __construct(private ?ExperimentsManager $experimentsManager = null)
    {
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
     * Whether notice should show in feed, based on whether user has
     * previously set up their channel name and brief description
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        return $this->experimentsManager->setUser($user)->isOn('minds-3131-onboarding-notices') &&
            !($user->getName() && $user->briefdescription);
    }
}
