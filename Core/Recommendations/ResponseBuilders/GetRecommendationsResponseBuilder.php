<?php

namespace Minds\Core\Recommendations\ResponseBuilders;

use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

class GetRecommendationsResponseBuilder
{
    /**
     * Build a successful response to be returned for the Http request
     * @param Response $response
     * @return JsonResponse
     */
    public function buildSuccessfulResponse(Response $response): JsonResponse
    {
        $response["entities"] = Exportable::_($response["entities"]);
        return new JsonResponse($response);
    }

    /**
     * Returns a bad request response to the request with a list of validation errors if any
     * @throws UserErrorException
     */
    public function buildBadRequestResponse(?ValidationErrorCollection $errors): JsonResponse
    {
        throw new UserErrorException(
            "",
            400,
            $errors
        );
    }
}
