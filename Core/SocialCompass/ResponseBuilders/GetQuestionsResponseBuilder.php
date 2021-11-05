<?php

namespace Minds\Core\SocialCompass\ResponseBuilders;

use Minds\Api\Exportable;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The response builder for the GET api/v3/social-compass/questions endpoint
 */
class GetQuestionsResponseBuilder
{
    /**
     * Build the response object for the Social Compass getQuestions endpoint
     * @param array $questions The list of Social Compass questions
     *
     *              [
     *                  "questions": BaseQuestion[],
     *                  "answerProvided": bool
     *              ]
     *
     * @return JsonResponse
     */
    public function build(array $questions): JsonResponse
    {
        return new JsonResponse(Exportable::_($questions));
    }
}
