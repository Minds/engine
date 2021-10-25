<?php

namespace Minds\Core\SocialCompass\ResponseBuilders;

use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

class StoreAnswersResponseBuilder
{
    /**
     * @param bool $wereAnswersStored
     * @return JsonResponse
     * @throws UserErrorException If it was not possible to store all the answers then the exception is thrown
     */
    public function buildResponse(bool $wereAnswersStored) : JsonResponse
    {
        if (!$wereAnswersStored) {
            throw new UserErrorException("it was not possible to store the Social Compass answers");
        }

        return new JsonResponse([
            'status' => 'success'
        ]);
    }

    /**
     * @param string $message The message to return to the user
     * @throws UserErrorException Throws an exception if the request does not contain the required body
     */
    public function buildBadRequestResponse(string $message)
    {
        throw new UserErrorException($message);
    }
}
