<?php

namespace Minds\Core\AccountQuality\ResponseBuilders;

use Minds\Core\AccountQuality\Models\UserQualityScore;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Builds the relevant responses for GET api/v3/account-quality/:targetUserId
 */
class GetAccountQualityScoreResponseBuilder
{
    /**
     * Builds the successful response object for the request
     * @param UserQualityScore $response
     * @return JsonResponse
     */
    public function buildSuccessfulResponse(UserQualityScore $response): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'results' => [
                'score' => abs($response->getScore() - 1)
            ]
        ]);
    }

    /**
     * @param ValidationErrorCollection $errors
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function buildBadRequestResponse(ValidationErrorCollection $errors): JsonResponse
    {
        throw new UserErrorException(
            "Some validation errors have been found with the request.",
            400,
            $errors
        );
    }
}
