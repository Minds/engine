<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Minds\Api\Exportable;
use Minds\Core\Di\Di;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The controller to handle the requests related to the feed for unseen top posts
 */
class Controller
{
    public function __construct(
        private ?ManagerInterface $manager = null
    ) {
        $this->manager = $this->manager ?? Di::_()->get("Feeds\UnseenTopFeed\Manager");
    }

    public function getUnseenTopFeed(ServerRequestInterface $request): JsonResponse
    {
        $totalEntitiesToRetrieve = $request->getQueryParams()["limit"];
        $response = $this->manager->getUnseenTopEntities($totalEntitiesToRetrieve)

        return new JsonResponse([
            'status' => 'success',
            'entities' => Exportable::_($response),
            'load-next' => $response->getPagingToken(),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
