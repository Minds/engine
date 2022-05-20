<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Minds\Common\Repository\Response;
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
     */
    public function __construct(private ?ElasticSearchManager $elasticSearchManager = null)
    {
        $this->elasticSearchManager = $this->elasticSearchManager ?? Di::_()->get("Feeds\Elastic\Manager");
    }

    /**
     * Get unseen top feed.
     * @param User $user - user to get for.
     * @param int $limit - defaults to 12.
     * @return Response - response from request.
     * @throws UserErrorException
     */
    public function getList(string $userGuid, int $limit = 12): Response
    {
        $response = $this->elasticSearchManager->getList([
            'limit' => $limit,
            'type' => 'activity',
            'algorithm' => 'latest',
            'subscriptions' => $userGuid,
            'single_owner_threshold' => 6,
            'period' => 'all', // legacy option
            'unseen' => true,
            'demoted' => true
        ]);

        $response->setPagingToken(null); // This endpoint doesn't support pagination yet.

        return $response;
    }
}
