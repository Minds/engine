<?php
namespace Minds\Core\Feeds;

use Minds\Api\Exportable;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Feeds Controller
 * @package Minds\Core\Feeds
 */
class Controller
{
    /** @var Elastic\Manager */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Config */
    protected $config;

    public function __construct(
        Elastic\Manager $manager = null,
        EntitiesBuilder $entitiesBuilder = null,
        Config $config = null
    ) {
        $this->manager = $manager ?? new Elastic\Manager();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Currently only supports reminds and quote post fetching, but can
     * easily be extended to the full feeds
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getFeed(ServerRequest $request): JsonResponse
    {
        $queryParams = $request->getQueryParams();
        $limit = (int) ($queryParams['limit'] ?? 12);
        $nextPage = (int) ($queryParams['next-page'] ?? time());

        $response = $this->manager->getList([
            'algorithm' => $queryParams['algorithm'] ?? 'latest',
            'period' => 'all',
            'type' => 'activity',
            'limit' => $limit,
            'hydrate_limit' => $limit,
            'remind_guid' => $queryParams['remind_guid'] ?? null,
            'quote_guid' => $queryParams['quote_guid'] ?? null,
            'from_timestamp' => $nextPage,
        ]);

        $entities = array_map(function ($feedItem) {
            return $feedItem->getEntity();
        }, $response->toArray());
    
        $nextPage = $response->getPagingToken();

        return new JsonResponse([
           'status' => 'success',
           'entities' => Exportable::_($entities),
           'load-next' => $nextPage,
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Fetches a default feed for a logged out user.
     * @param ServerRequest $request - params: 'limit' and 'next-page'.
     * @return JsonResponse - JSON response containing status, entities and load-next for pagination.
     */
    public function getLoggedOutFeed(ServerRequest $request): JsonResponse
    {
        $queryParams = $request->getQueryParams();
        $limit = (int) ($queryParams['limit'] ?? 12);
        $nextPage = (int) ($queryParams['next-page'] ?? 0);

        $recommendationsUserGuid = $this->config->get('default_recommendations_user') ?? '100000000000000519';
        
        $response = $this->manager->getList([
            'cache_key' => $recommendationsUserGuid,
            'subscriptions' => $recommendationsUserGuid,
            'access_id' => 2,
            'limit' => $limit,
            'type' => 'activity',
            'algorithm' => 'latest', // TODO: switch to top
            'period' => '1y',
            'single_owner_threshold' => 0,
            'from_timestamp' => $nextPage,
            'nsfw' => []
        ]);

        return new JsonResponse([
            'status' => 'success',
            'entities' => Exportable::_($response),
            'load-next' => $response->getPagingToken(),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
