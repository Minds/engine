<?php

namespace Minds\Core\SocialCompass\ResponseBuilders;

use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The response builder for the POST api/v3/social-compass/answers endpoint
 */
class StoreAnswersResponseBuilder implements AnswersResponseBuilderInterface
{
    public function buildResponse(bool $wereAnswersStored): JsonResponse
    {
        if (!$wereAnswersStored) {
            throw new ServerErrorException("it was not possible to store the Social Compass answers");
        }

        return new JsonResponse([
            'status' => 'success'
        ]);
    }

    public function buildBadRequestResponse(string $message): void
    {
        throw new UserErrorException($message);
    }
}
