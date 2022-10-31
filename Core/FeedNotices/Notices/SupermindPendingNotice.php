<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Supermind\Manager as SupermindManager;
use Minds\Core\Supermind\SupermindRequestStatus;

/**
 * Feed notice for pending Superminds.
 */
class SupermindPendingNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'top';

    // notice key / identifier.
    private const KEY = 'supermind-pending';

    public function __construct(
        private ?SupermindManager $supermindManager = null
    ) {
        $this->supermindManager ??= Di::_()->get('Supermind\Manager');
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
     * "pending" supermind offers. Note, the actual supermind state `PENDING`
     * is an internal state - we want to check for created, non expired offers.
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        $latestCreatedSupermind = $this->supermindManager->setUser($user)
            ->getReceivedRequests(
                offset: 0,
                limit: 1,
                status: SupermindRequestStatus::CREATED
            )->first();

        return $latestCreatedSupermind && !$latestCreatedSupermind->isExpired();
    }
}
