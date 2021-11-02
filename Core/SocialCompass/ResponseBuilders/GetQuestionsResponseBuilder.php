<?php

namespace Minds\Core\SocialCompass\ResponseBuilders;

use Minds\Api\Exportable;
use Zend\Diactoros\Response\JsonResponse;

class GetQuestionsResponseBuilder
{
    /**
     * Build the response object for the Social Compass getQuestions endpoint
     * @param array $questions The list of Social Compass questions
     * @return JsonResponse
     */
    public function build(array $questions): JsonResponse
    {
        return new JsonResponse(Exportable::_($questions));
    }
}
