<?php

namespace Minds\Core\Feeds\User;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use Minds\Entities\User;

/**
 * Manager handling functions for a feed related to a specific user.
 */
class Manager
{
    // instance user.
    private ?User $user;

    /**
     * Constructor.
     * @param ElasticManager|null $feedManager - feed manager.
     */
    public function __construct(
        private ?ElasticManager $feedManager = null,
        private ?PsrWrapper $cache = null,
    ) {
        $this->feedManager ??= Di::_()->get('Feeds\Elastic\Manager');
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Set instance user.
     * @param User $user - user to set.
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Whether user has made at least one post.
     * @param string $period - period to check, defaults to 1y.
     * @throws \Exception - on error.
     * @return bool true if user has made at least one post.
     */
    public function hasMadePosts(string $period = '1y'): bool
    {
        if ($this->getHasMadePostsFromCache($this->user->getGuid())) {
            return true;
        }

        $opts = [
            'container_guid' => $this->user->getGuid(),
            'algorithm' => 'latest',
            'period' => $period,
            'type' => 'activity'
        ];

        return $this->feedManager->getCount(
            opts: $opts,
            handleExceptions: false
        ) > 0;
    }

    /**
     * Get whether a user has made posts from the cache.
     * @param string $userGuid - the users guid.
     * @return bool true if we have stored in the cache that a user has made posts.
     */
    public function getHasMadePostsFromCache(string $userGuid): bool
    {
        return (bool) $this->cache->get("$userGuid:posted");
    }

    /**
     * Set that a user has made posts in cache.
     * @param string $userGuid - user guid to set for.
     * @return self
     */
    public function setHasMadePostsInCache(string $userGuid): self
    {
        $this->cache->set("$userGuid:posted", true);
        return $this;
    }
}
