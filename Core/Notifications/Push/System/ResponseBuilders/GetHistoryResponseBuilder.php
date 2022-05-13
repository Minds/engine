<?php

namespace Minds\Core\Notifications\Push\System\ResponseBuilders;

use Minds\Common\Repository\Response;
use Zend\Diactoros\Response\JsonResponse;

/**
 *
 */
class GetHistoryResponseBuilder
{
    public function successfulResponse(Response $response): JsonResponse
    {
        $data = array_merge([
            'status' => 'success',
        ], $response->toArray());

        return new JsonResponse($data);
    }
}
