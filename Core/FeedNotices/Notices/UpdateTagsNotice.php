<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Entities\User;

/**
 * Feed notice to prompt user to update their tags.
 * @deprecated
 */
class UpdateTagsNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'inline';

    // notice key / identifier.
    private const KEY = 'update-tags';

    /**
     * Constructor.
     * @param ?UserHashtagsManager $userHashtagsManager - manager for user hashtags.
     */
    public function __construct(
        private ?UserHashtagsManager $userHashtagsManager = null
    ) {
        $this->userHashtagsManager ??= new UserHashtagsManager();
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
     * set hashtags previously.
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        return !($this->userHashtagsManager->setUser($user)->hasSetHashtags());
    }
}
