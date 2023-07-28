<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Experiments\Manager as ExperimentsManager;

/**
 * Feed notice to inform users about the Boost Partners program.
 */
class BoostPartnersNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'inline';

    // notice key / identifier.
    private const KEY = 'boost-partners';

    public function __construct(
        private ?ExperimentsManager $experimentsManager = null
    ) {
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
     * Whether notice should show in feed - true if the experiment is enabled
     * and their phone number has been verified
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        return $this->experimentsManager->setUser($user)->isOn('epic-303-boost-partners') && $user->getPhoneNumberHash();
    }
}
