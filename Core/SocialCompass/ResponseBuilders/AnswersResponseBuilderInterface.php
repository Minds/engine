<?php

namespace Minds\Core\SocialCompass\ResponseBuilders;

use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The interface define the signatures for every response builder
 * used by every Social Compass answer related endpoint
 */
interface AnswersResponseBuilderInterface
{
    /**
     * Returns a successful Json response if the answers have been stored.
     * Throws a UserErrorException if at least one question was not stored successfully.
     * @param bool $wereAnswersStored
     * @return JsonResponse
     * @throws ServerErrorException
     */
    public function buildResponse(bool $wereAnswersStored): JsonResponse;

    /**
     * Throws a UserErrorException which leads to a 400 - Bad Request response to the FE
     * @param string $message
     * @throws UserErrorException
     */
    public function buildBadRequestResponse(string $message): void;
}
