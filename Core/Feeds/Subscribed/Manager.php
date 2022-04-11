<?php

namespace Minds\Core\Feeds\Subscribed;

use Exception;

use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic;
use Minds\Entities\User;

class Manager
{
    public function __construct(
        private ?Elastic\Manager $feedsElasticManager = null
    ) {
        $this->feedsElasticManager = $this->feedsElasticManager ?? Di::_()->get("Feeds\Elastic\Manager");
    }

    /**
     * @param User $user
     * @param int $fromTimestamp
     * @return int
     * @throws Exception
     */
    public function getLatestCount(
        User $user,
        int $fromTimestamp
    ): int {
        $opts = [
            'limit' => 100,
            'cache_key' => $user->getGuid(),
            'subscriptions' => $user->getGuid(),
            'from_timestamp' => $fromTimestamp,
            'access_id' => 2,
            'type' => 'activity', // TODO: should we support other types?
            'algorithm' => 'latest',
            'period' => '1y',
            'reverse_sort' => true,
            'hide_own_posts' => true,
            // 'sync' => $sync,
            // 'custom_type' => $custom_type,
            // 'query' => $query ?? null,
            // 'nsfw' => $nsfw,
            // 'single_owner_threshold' => 0,
            // 'portrait' => isset($_GET['portrait']),
            // 'include_group_posts' => isset($_GET['include_group_posts']),
        ];

        return $this->feedsElasticManager->getCount($opts);
    }
}
