<?php

namespace Minds\Core\SocialCompass\ResponseBuilders;

use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

class UpdateAnswersResponseBuilder
{
    /**
     * Build the successful response object to the PUT /social-compass/answers request.
     * Throws a UserErrorException otherwise
     * @param bool $wereAnswersStored
     * @return JsonResponse
     * @throws UserErrorException If it was not possible to update all the answers then the exception is thrown
     */
    public function buildResponse(bool $wereAnswersStored): JsonResponse
    {
        if ($wereAnswersStored) {
            return new JsonResponse([
                'status' => 'success'
            ]);
        }

        throw new UserErrorException("it was not possible to store the Social Compass answers");
    }

    /**
     * The request sent to PUT /social-compass/answers did not pass validation
     * Throws a UserErrorException
     * @param string $message The message to return to the user
     * @throws UserErrorException Throws an exception if the request does not contain the required body
     */
    public function buildBadRequestResponse(string $message)
    {
        throw new UserErrorException($message);
    }
}
