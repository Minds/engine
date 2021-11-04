<?php

namespace Minds\Core\SocialCompass\ResponseBuilders;

use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The response builder for the PUT api/v3/social-compass/answers endpoint
 */
class UpdateAnswersResponseBuilder implements AnswersResponseBuilderInterface
{
    public function buildResponse(bool $wereAnswersStored): JsonResponse
    {
        if ($wereAnswersStored) {
            return new JsonResponse([
                'status' => 'success'
            ]);
        }

        throw new UserErrorException("it was not possible to store the Social Compass answers");
    }

    public function buildBadRequestResponse(string $message): void
    {
        throw new UserErrorException($message);
    }
}
