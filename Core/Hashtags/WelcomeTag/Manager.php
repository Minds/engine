<?php
declare(strict_types=1);

namespace Minds\Core\Hashtags\WelcomeTag;

use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\User\Manager as FeedsUserManager;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 * Manager that handles the appending and stripping of "welcome" tags
 * intended to flag new users posts to give them more discoverability.
 */
class Manager
{
    // Welcome tag text.
    const WELCOME_TAG = 'hellominds';

    public function __construct(
        protected ?Resolver $entitiesResolver = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?FeedsUserManager $feedUserManager = null,
        protected ?Logger $logger = null
    ) {
        $this->entitiesResolver ??= new Resolver();
        $this->entitiesBuilder ??= new EntitiesBuilder();
        $this->feedUserManager ??= Di::_()->get('Feeds\User\Manager');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Append welcome tag to an activities tags.
     * @param Activity $activity - activity to apply tag to.
     * @return Activity changed activity.
     */
    public function append(Activity $activity): Activity
    {
        $tags = $activity->getTags() ?? [];
        $tags[] = self::WELCOME_TAG;
        $activity->setTags($tags);
        return $activity;
    }

    /**
     * Strip existing welcome tags from an activities tags array.
     * @param Activity $activity - activity to strip of any welcome tag.
     * @return Activity changed activity.
     */
    public function strip(Activity $activity): Activity
    {
        $tags = $activity->getTags();
        $tagIndex = array_search(self::WELCOME_TAG, array_map('strtolower', $tags), true);

        if ($tagIndex !== false) {
            unset($tags[$tagIndex]);
            $tags = array_values($tags);
            $activity->setTags($tags);
        }

        return $activity;
    }

    /**
     * Whether welcome tag should be appended.
     * @param Activity $activity - activity to check for.
     * @return bool true if welcome tag should be appended.
     */
    public function shouldAppend(Activity $activity): bool
    {
        return !$this->hasMadeActivityPosts((string) $activity->getOwnerGuid());
    }

    /**
     * Whether user has made a single activity post.
     * @param string $ownerGuid - guid of the owner.
     * @throws ServerErrorException - if no user is found.
     * @return bool - true if user has made a single activity post.
     */
    public function hasMadeActivityPosts(string $ownerGuid): bool
    {
        if ($this->feedUserManager->getHasMadePostsFromCache($ownerGuid)) {
            return true;
        }

        $owner = $this->entitiesBuilder->single($ownerGuid);
        if (!$owner || !($owner instanceof User)) {
            throw new ServerErrorException("No user found for owner guid: $ownerGuid");
        }

        try {
            $hasMadePosts = $this->feedUserManager->setUser($owner)
                ->hasMadePosts();

            $this->feedUserManager->setHasMadePostsInCache($ownerGuid);

            return $hasMadePosts;
        } catch (\Exception $e) {
            $this->logger->error($e);
            // presume true so we don't wrongly index a users post who already has other posts.
            return true;
        };
    }
}
