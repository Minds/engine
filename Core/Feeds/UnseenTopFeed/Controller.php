<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The controller to handle the requests related to the feed for unseen top posts
 */
class Controller
{
    public function getUnseenTopFeed(ServerRequestInterface $request): JsonResponse
    {
    }
}
