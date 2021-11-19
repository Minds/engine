<?php

namespace Minds\Core\Feeds\UnseenTopFeed\ResponseBuilders;

use Zend\Diactoros\Response\JsonResponse;

class GetUnseenTopFeedResponseBuilder
{
    public function build(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
