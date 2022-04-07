<?php

namespace Minds\Core\Feeds\Subscribed\ResponseBuilders;

use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

class SubscribedLatestCountResponseBuilder
{
    public function buildSuccessfulResponse(int $count): JsonResponse
    {
        return new JsonResponse(
            [
                'status' => 'success',
                'count' => $count,
            ],
            200,
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
