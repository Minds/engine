<?php

namespace Minds\Core\SocialCompass\ResponseBuilders;

use Zend\Diactoros\Response\JsonResponse;

class GetQuestionsResponseBuilder
{
    public function __construct(
        private string $currenQuestionSetVersion
    )
    {}

    /**
     * Build the response object for the Social Compass getQuestions endpoint
     * @param array|null $answers
     * @return JsonResponse
     */
    public function build(?array $answers) : JsonResponse
    {
        $questionsList = new ${"QuestionsManifestV$this->currenQuestionSetVersion"}();

        $results = [];

        foreach ($questionsList as $questionId)
        {
            $question = new $questionId();

            if (isset($answers[$questionId])) {
                $question->currentValue = $answers[$questionId]['current_value'];
            }

            array_push($results, $question);
        }

        return new JsonResponse($results);
    }
}
