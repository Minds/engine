<?php

namespace Minds\Core\SocialCompass\ResponseBuilders;

use Zend\Diactoros\Response\JsonResponse;

class StoreAnswersResponseBuilder
{
    public function buildResponse(bool $wereAnswersStored) : JsonResponse
    {
        if ($wereAnswersStored) {
            return new JsonResponse([
                'status' => 'success'
            ]);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'it was not possible to store the Social Compass answers'
        ]);
    }

    public function buildBadRequestResponse(string $message) : JsonResponse
    {
        return new JsonResponse(
            [
            'status' => 'error',
            'message' => $message
        ],
            400
        );
    }
}
