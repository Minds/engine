<?php

namespace Minds\Core\Feeds\ClusteredRecommendations\ResponseBuilders;

use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Class responsible to build responses for endpoint 'ap/v3/newsfeed/feed/clustered-recommendations'
 */
class ClusteredRecommendationsResponseBuilder
{
    /**
     * Builds a successful response JSON object to return to the FE
     * @param Response $results
     * @return JsonResponse
     */
    public function successfulResponse(Response $results): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'entities' => Exportable::_($results),
            'load-next' => $results->getPagingToken()
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Throws a Bad Request exception if request validation errors were found
     * @throws UserErrorException
     */
    public function throwBadRequestResponse(ValidationErrorCollection $errors): void
    {
        throw new UserErrorException(
            "Errors were encountered during the request validation",
            404,
            $errors
        );
    }
}
