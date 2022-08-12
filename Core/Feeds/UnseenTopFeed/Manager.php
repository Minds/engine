<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

/**
 * Managing the feed for unseen top posts.
 */
class Manager
{
    /**
     * Constructor.
     * @param ?ElasticSearchManager $elasticSearchManager - elasticsearch manager.
     * @param ?Config $config - config.
     */
    public function __construct(
        private ?ElasticSearchManager $elasticSearchManager = null,
        private ?Config $config = null
    ) {
        $this->elasticSearchManager = $this->elasticSearchManager ?? Di::_()->get("Feeds\Elastic\Manager");
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Get unseen top feed.
     * @param User $user - user to get for.
     * @param int $limit - defaults to 12.
     * @param int $fromTimestamp
     * @return Response - response from request.
     * @throws UserErrorException
     */
    public function getList(string $userGuid, int $limit = 12, int $fromTimestamp = null, bool $excludeSelf = false): Response
    {
        $response = $this->elasticSearchManager->getList([
            'limit' => $limit,
            'type' => 'activity',
            'algorithm' => $this->getAlgorithm(),
            'subscriptions' => $userGuid,
            'single_owner_threshold' => 6,
            'period' => 'all', // legacy option
            'unseen' => true,
            'demoted' => $excludeSelf ? false : true,
            'to_timestamp' => $fromTimestamp,
            'from_timestamp' => $fromTimestamp ? time() * 1000 : null,
            'exclude' => $excludeSelf ? [ $userGuid ] : null,
        ]);

        $response->setPagingToken(null); // This endpoint doesn't support pagination yet.

        return $response;
    }

    /**
     * Gets algorithm for unseen top feed - defaults to `top` as it should be
     * for production but can be overridden to use different algorithms
     * for testing purposes.
     * @return string algorithm to search for.
     */
    private function getAlgorithm(): string
    {
        return $this->config->get('unseen_top_algorithm') ?? 'top';
    }
}
