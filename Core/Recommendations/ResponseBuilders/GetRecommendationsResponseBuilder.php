<?php

namespace Minds\Core\Recommendations\ResponseBuilders;

use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

class GetRecommendationsResponseBuilder
{
    public function buildSuccessfulResponse(Response $response): JsonResponse
    {
        $response["entities"] = Exportable::_($response["entities"]);
        return new JsonResponse($response);
    }

    /**
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
