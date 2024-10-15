<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Services;

use Minds\Core\Admin\Repositories\HashtagExclusionRepository;
use Minds\Core\Hashtags\Trending\Cache;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 * Service for handling admin exclusion of hashtags.
 */
class HashtagExclusionService
{
    public function __construct(
        private HashtagExclusionRepository $repository,
        private Cache $cache
    ) {
    }

    /**
     * Exclude a hashtag.
     * @param string $tag - the hashtag to exclude.
     * @param User $admin - the admin user performing the action.
     * @return bool - true if the hashtag was excluded, false otherwise.
     */
    public function excludeHashtag(string $tag, User $user): bool
    {
        $success = $this->repository->upsertTag($tag, (int) $user->getGuid());
        if ($success) {
            $this->cache->invalidate();
        }
        return $success;
    }

    /**
     * Remove a hashtag exclusion.
     * @param string $tag - the hashtag to remove the exclusion for.
     * @return bool - true if the hashtag exclusion was removed, false otherwise.
     * @throws ServerErrorException
     */
    public function removeHashtagExclusion(string $tag): bool
    {
        $success = $this->repository->removeTag($tag);
        if ($success) {
            $this->cache->invalidate();
        }
        return $success;
    }

    /**
     * Get excluded hashtags.
     * @param int|null $after - the timestamp to start from.
     * @param int|null $limit - the number of hashtags to return.
     * @param bool &$hasNextPage - whether there are more results.
     * @return iterable<HashtagExclusionNode> - iterable of nodes.
     */
    public function getExcludedHashtags(int $after = null, int $limit = null, bool &$hasNextPage = false): iterable
    {
        return $this->repository->getTags(
            after: $after,
            limit: $limit,
            hasNextPage: $hasNextPage
        );
    }
}
