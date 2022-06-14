<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Feeds\Elastic\Manager as FeedsManager;

/**
 * Feed notice to prompt a user to join rewards and verify their uniqueness.
 */
class VerifyUniquenessNotice extends AbstractNotice
{
    const MINIMUM_ACCOUNT_AGE = 259200;

    // location of notice in feed.
    private const LOCATION = 'inline';

    // notice key / identifier.
    private const KEY = 'verify-uniqueness';

    public function __construct(
        private ?FeedsManager $feedManager = null,
        private ?UpdateTagsNotice $updateTagsNotice = null,
        private ?SetupChannelNotice $setupChannelNotice = null
    ) {
        $this->feedManager ??= Di::_()->get('Feeds\Elastic\Manager');
        $this->updateTagsNotice ??= new UpdateTagsNotice();
        $this->setupChannelNotice ??= new SetupChannelNotice();
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
     * no stored phone number hash and meets prerequisites.
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        return !$user->getPhoneNumberHash() &&
            $this->meetsPrerequisites($user);
    }

    /**
     * Meets not directly related prerequisite steps to see this notice.
     * @param User $user - user to check.
     * @return boolean true if user meets prerequisites.
     */
    public function meetsPrerequisites(User $user): bool
    {
        // pre-requisite steps explicitly specified so dismissal does not skip.
        return $user->isTrusted() &&
            $user->getAge() > self::MINIMUM_ACCOUNT_AGE &&
            $this->hasMadePosts($user) &&
            !$this->setupChannelNotice->shouldShow($user) &&
            !$this->updateTagsNotice->shouldShow($user);
    }

    /**
     * True if user has made a post previously.
     * @param User $user - user to check.
     * @return boolean - true if user has previously made a post.
     */
    private function hasMadePosts(User $user) {
        $opts = [
            'container_guid' => $user->getGuid(),
            'limit' => 1,
            'algorithm' => 'latest',
            'period' => '1y',
            'type' => 'activity'
        ];
        $result = $this->feedManager->getList($opts);
        return $result->count() > 0;
    }
}
