<?php
namespace Minds\Core\Feeds;

use Minds\Api\Exportable;
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

    public function __construct(Elastic\Manager $manager = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->manager = $manager ?? new Elastic\Manager();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
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
}
