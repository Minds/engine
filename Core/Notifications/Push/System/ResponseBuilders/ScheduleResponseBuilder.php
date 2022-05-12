<?php

namespace Minds\Core\Notifications\Push\System\ResponseBuilders;

use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Zend\Diactoros\Response\JsonResponse;

/**
 *
 */
class ScheduleResponseBuilder
{
    public function successfulResponse(Response $response): JsonResponse
    {
        $data = array_merge([
            'status' => 'success',
        ], Exportable::_($response)->export());

        return new JsonResponse($data);
    }
}
