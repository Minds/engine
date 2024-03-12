<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipManager;

/**
 * Feed notice to encourage users to join groups.
 *
 * It is not included in the Notices\Manager for now
 * because it is only applicable to the newsfeed groups tab
 */
class NoGroupsNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'top';

    // notice key / identifier.
    private const KEY = 'no-groups';

    /**
     * Constructor.
     * @param ?GroupMembershipManager $groupMembershipManager - manager for group membership.
     * @param ?Config $config - config.
     */
    public function __construct(
        private ?GroupMembershipManager $groupMembershipManager = null,
        private ?Config $config = null
    ) {
        $this->groupMembershipManager ??= Di::_()->get(GroupMembershipManager::class);
        parent::__construct(config: $config);
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
        return false;
    }

    /**
     * Whether notice should show in feed,
     * based on whether user is not a member of any groups yet
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        if ($this->isTenantContext()) {
            return false;
        }

        $groupGuids = $this->groupMembershipManager->getGroupGuids($user, 1);

        return sizeof($groupGuids) == 0;
    }
}
