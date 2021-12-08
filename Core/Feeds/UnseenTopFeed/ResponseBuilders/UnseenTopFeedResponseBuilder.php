<?php

namespace Minds\Core\Feeds\UnseenTopFeed\ResponseBuilders;

use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

class UnseenTopFeedResponseBuilder
{
    public function buildSuccessfulResponse(Response $response): JsonResponse
    {
        return new JsonResponse(
            [
                'status' => 'success',
                'entities' => Exportable::_($response),
                'load-next' => $response->getPagingToken(),
            ],
            200,
            [],
            JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    /**
     * @throws UserErrorException
     */
    public function buildBadRequestResponse(?ValidationErrorCollection $errors): JsonResponse
    {
        throw new UserErrorException(
            "There were some errors validating the request properties.",
            400,
            $errors
        );
    }
}
