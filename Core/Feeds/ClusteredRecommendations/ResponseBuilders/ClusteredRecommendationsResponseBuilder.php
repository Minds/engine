<?php

namespace Minds\Core\Feeds\ClusteredRecommendations\ResponseBuilders;

use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

class ClusteredRecommendationsResponseBuilder
{
    public function successfulResponse(Response $results): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'entities' => Exportable::_($results),
            'load-next' => $results->getPagingToken()
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * @throws UserErrorException
     */
    public function badRequestResponse(ValidationErrorCollection $errors): void
    {
        throw new UserErrorException(
            "Errors were encountered during the request validation",
            404,
            $errors
        );
    }
}
