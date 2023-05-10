<?php
declare(strict_types=1);

namespace Minds\Core\Hashtags\WelcomeTag;

use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\User\Manager as FeedsUserManager;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 * Manager that handles the appending and removal of "welcome" tags
 * intended to be used to flag new users posts to give them more discoverability.
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
     * Append welcome tag to an array of tags.
     * @param array $tags - array to append tag to.
     * @return array $tags changed array.
     */
    public function append(array $tags): array
    {
        // do not append if tag already present.
        if ($this->hasTag($tags)) {
            return $tags;
        }

        $tags[] = self::WELCOME_TAG;
        return $tags;
    }

    /**
     * Remove existing welcome tags from an array of tags.
     * @param array $tags - array of tags to remove from.
     * @return array changed array.
     */
    public function remove(array $tags): array
    {
        $tagsCount = count($tags);

        for ($i = 0; $i < $tagsCount; $i ++) {
            if (strtolower($tags[$i]) === self::WELCOME_TAG) {
                unset($tags[$i]);
            }
        }

        return array_values($tags);
    }

    /**
     * Whether welcome tag should be appended to a tags array.
     * @param string $ownerGuid - owner to apply tag to.
     * @return bool true if welcome tag should be appended.
     */
    public function shouldAppend(string $ownerGuid): bool
    {
        return !$this->hasMadeActivityPosts($ownerGuid);
    }

    /**
     * Whether an array has the welcome tag in it.
     * @param array $tags - tags to check.
     * @return bool true if array has welcome tag in it.
     */
    public function hasTag(array $tags): bool
    {
        return array_search(self::WELCOME_TAG, array_map('strtolower', $tags), true) !== false;
    }

    /**
     * Whether user has made a single activity post.
     * @param string $ownerGuid - guid of the owner.
     * @throws ServerErrorException - if no user is found.
     * @return bool - true if user has made a single activity post.
     */
    private function hasMadeActivityPosts(string $ownerGuid): bool
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
