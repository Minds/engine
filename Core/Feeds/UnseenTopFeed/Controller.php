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
        return new JsonResponse(Exportable::_($this->manager->getUnseenTopEntities($totalEntitiesToRetrieve)));
    }
}
